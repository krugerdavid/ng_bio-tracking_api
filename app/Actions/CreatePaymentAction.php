<?php

namespace App\Actions;

use App\Models\Payment;
use App\Repositories\PaymentRepository;

class CreatePaymentAction implements Action
{
    public function __construct(private PaymentRepository $repository) {}

    /**
     * @param array $data
     * @return Payment
     */
    public function execute(...$args): Payment
    {
        [$data] = $args;
        return $this->repository->create($data);
    }
}
