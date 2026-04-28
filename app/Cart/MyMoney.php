<?php

namespace App\Cart;

use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Formatter\IntlMoneyFormatter;
use Money\Money;
use NumberFormatter;

class MyMoney implements \JsonSerializable
{
    protected Money $money;

    public function __construct(int|string $value)
    {
        $this->money = new Money($value, new Currency('EUR'));
    }

    public function amount(): string
    {
        return $this->money->getAmount();
    }

    public function formatted(): string
    {
        if (class_exists(NumberFormatter::class)) {
            $currencies = new ISOCurrencies;
            $numberFormatter = new NumberFormatter('it_IT', NumberFormatter::CURRENCY);
            $moneyFormatter = new IntlMoneyFormatter($numberFormatter, $currencies);

            return $moneyFormatter->format($this->money);
        }

        return number_format(((int) $this->money->getAmount()) / 100, 2, ',', '.') . ' EUR';
    }

    public function instance(): Money
    {
        return $this->money;
    }

    public function add(self $money): self
    {
        $this->money = $this->money->add($money->instance());

        return $this;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'amount' => (int) $this->money->getAmount(),
            'formatted' => $this->formatted(),
        ];
    }
}
