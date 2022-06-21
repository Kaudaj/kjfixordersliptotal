<?php
/**
 * Copyright since 2019 Kaudaj
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@kaudaj.com so we can send you a copy immediately.
 *
 * @author    Kaudaj <info@kaudaj.com>
 * @copyright Since 2019 Kaudaj
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

namespace Kaudaj\Module\FixOrderSlipTotal\Adapter\Order\QueryHandler;

use Currency;
use DateTimeImmutable;
use Order;
use OrderInvoice;
use OrderSlip;
use PrestaShop\PrestaShop\Adapter\Configuration;
use PrestaShop\PrestaShop\Adapter\Order\AbstractOrderHandler;
use PrestaShop\PrestaShop\Adapter\Order\QueryHandler\GetOrderForViewingHandler as OriginalGetOrderForViewingHandler;
use PrestaShop\PrestaShop\Core\Domain\Order\OrderDocumentType;
use PrestaShop\PrestaShop\Core\Domain\Order\Query\GetOrderForViewing;
use PrestaShop\PrestaShop\Core\Domain\Order\QueryHandler\GetOrderForViewingHandlerInterface;
use PrestaShop\PrestaShop\Core\Domain\Order\QueryResult\OrderDocumentForViewing;
use PrestaShop\PrestaShop\Core\Domain\Order\QueryResult\OrderDocumentsForViewing;
use PrestaShop\PrestaShop\Core\Domain\Order\QueryResult\OrderForViewing;
use PrestaShop\PrestaShop\Core\Domain\Shop\ValueObject\ShopConstraint;
use PrestaShop\PrestaShop\Core\Localization\Exception\LocalizationException;
use PrestaShop\PrestaShop\Core\Localization\Locale;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Handle getting order for viewing
 *
 * @internal
 */
final class GetOrderForViewingHandler extends AbstractOrderHandler implements GetOrderForViewingHandlerInterface
{
    /**
     * @var Locale
     */
    private $locale;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var int
     */
    private $contextLanguageId;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var OriginalGetOrderForViewingHandler
     */
    private $decoratedHandler;

    public function __construct(
        OriginalGetOrderForViewingHandler $decoratedHandler,
        TranslatorInterface $translator,
        int $contextLanguageId,
        Locale $locale,
        Configuration $configuration
    ) {
        $this->decoratedHandler = $decoratedHandler;
        $this->translator = $translator;
        $this->contextLanguageId = $contextLanguageId;
        $this->locale = $locale;
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(GetOrderForViewing $query): OrderForViewing
    {
        $order = $this->decoratedHandler->getOrder($query->getOrderId());
        $orderForViewing = $this->decoratedHandler->handle($query);

        return new OrderForViewing(
            $orderForViewing->getId(),
            $orderForViewing->getCurrencyId(),
            $orderForViewing->getCarrierId(),
            $orderForViewing->getCarrierName(),
            $orderForViewing->getShopId(),
            $orderForViewing->getReference(),
            $orderForViewing->isVirtual(),
            $orderForViewing->getTaxMethod(),
            $orderForViewing->isTaxIncluded(),
            $orderForViewing->isValid(),
            $orderForViewing->hasBeenPaid(),
            $orderForViewing->hasInvoice(),
            $orderForViewing->isDelivered(),
            $orderForViewing->isShipped(),
            $orderForViewing->isInvoiceManagementIsEnabled(),
            $orderForViewing->getCreatedAt(),
            $orderForViewing->getCustomer(),
            $orderForViewing->getShippingAddress(),
            $orderForViewing->getInvoiceAddress(),
            $orderForViewing->getProducts(),
            $orderForViewing->getHistory(),
            $this->getOrderDocuments($order),
            $orderForViewing->getShipping(),
            $orderForViewing->getReturns(),
            $orderForViewing->getPayments(),
            $orderForViewing->getMessages(),
            $orderForViewing->getPrices(),
            $orderForViewing->getDiscounts(),
            $orderForViewing->getSources(),
            $orderForViewing->getLinkedOrders(),
            $orderForViewing->getShippingAddressFormatted(),
            $orderForViewing->getInvoiceAddressFormatted(),
            $orderForViewing->getNote()
        );
    }

    /**
     * @param Order $order
     *
     * @return OrderDocumentsForViewing
     *
     * @throws LocalizationException
     */
    protected function getOrderDocuments(Order $order): OrderDocumentsForViewing
    {
        $currency = new Currency($order->id_currency);
        $documents = $order->getDocuments();

        $documentsForViewing = [];

        /** @var OrderInvoice|OrderSlip $document */
        foreach ($documents as $document) {
            $type = null;
            $number = null;
            $amount = null;
            $numericAmount = null;
            $amountMismatch = null;
            $isAddPaymentAllowed = false;

            if ($document instanceof OrderInvoice) {
                $type = isset($document->is_delivery) ? OrderDocumentType::DELIVERY_SLIP : OrderDocumentType::INVOICE;
            } elseif ($document instanceof OrderSlip) {
                $type = OrderDocumentType::CREDIT_SLIP;
            }

            if (OrderDocumentType::INVOICE === $type) {
                $number = $document->getInvoiceNumberFormatted(
                    $this->contextLanguageId,
                    $order->id_shop
                );

                if ($document->getRestPaid()) {
                    $isAddPaymentAllowed = true;
                }
                $amount = $this->locale->formatPrice($document->total_paid_tax_incl, $currency->iso_code);
                $numericAmount = $document->total_paid_tax_incl;

                if ($document->getTotalPaid()) {
                    if ($document->getRestPaid() > 0) {
                        $amountMismatch = sprintf(
                            '%s %s',
                            $this->locale->formatPrice($document->getRestPaid(), $currency->iso_code),
                            $this->translator->trans('not paid', [], 'Admin.Orderscustomers.Feature')
                        );
                    } elseif ($document->getRestPaid() < 0) {
                        $amountMismatch = sprintf(
                            '%s %s',
                            $this->locale->formatPrice($document->getRestPaid(), $currency->iso_code),
                            $this->translator->trans('overpaid', [], 'Admin.Orderscustomers.Feature')
                        );
                    }
                }
            } elseif (OrderDocumentType::DELIVERY_SLIP === $type) {
                $conf = $this->configuration->get(
                    'PS_DELIVERY_PREFIX',
                    null,
                    ShopConstraint::shop((int) $order->id_shop)
                );
                $number = sprintf(
                    '%s%06d',
                    $conf[$this->contextLanguageId] ?? '',
                    $document->delivery_number
                );
                $amount = $this->locale->formatPrice(
                    $document->total_paid_tax_incl,
                    $currency->iso_code
                );
                $numericAmount = $document->total_paid_tax_incl;
            } elseif (OrderDocumentType::CREDIT_SLIP === $type) {
                $conf = $this->configuration->get('PS_CREDIT_SLIP_PREFIX');
                $number = sprintf(
                    '%s%06d',
                    $conf[$this->contextLanguageId] ?? '',
                    $document->id
                );

                $total_cart_rule = 0;
                $cart_rules = $order->getCartRules();

                if ($document->order_slip_type == 1 && is_array($cart_rules)) {
                    foreach ($cart_rules as $cart_rule) {
                        $total_cart_rule += $cart_rule['value'];
                    }
                }

                $amount = $this->locale->formatPrice(
                    $document->total_products_tax_incl + $document->total_shipping_tax_incl - $total_cart_rule,
                    $currency->iso_code
                );
                $numericAmount = $document->total_products_tax_incl + $document->total_shipping_tax_incl - $total_cart_rule;
            }

            $documentsForViewing[] = new OrderDocumentForViewing(
                $document->id,
                $type,
                new DateTimeImmutable($document->date_add),
                $number,
                $numericAmount,
                $amount,
                $amountMismatch,
                $document instanceof OrderInvoice ? $document->note : null,
                $isAddPaymentAllowed
            );
        }

        $canGenerateInvoice = $this->configuration->get('PS_INVOICE') &&
            count($order->getInvoicesCollection()) &&
            $order->invoice_number;

        $canGenerateDeliverySlip = (bool) $order->delivery_number;

        return new OrderDocumentsForViewing(
            $canGenerateInvoice,
            $canGenerateDeliverySlip,
            $documentsForViewing
        );
    }
}
