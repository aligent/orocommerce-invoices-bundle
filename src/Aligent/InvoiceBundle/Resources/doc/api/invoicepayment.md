# Aligent\InvoiceBundle\Entity\InvoicePayment

## ACTIONS

### get

Retrieve a specific Invoice Payment.

{@inheritdoc}

### get_list

Retrieve a collection of Invoice Payments.

{@inheritdoc}

## FIELDS

### active
Is InvoicePayment active? (`true`: Pending/Failed Payment, `false`: Successful/Complete Payment)

### amount
Subtotal of Invoice Payment (Amount to be paid against Invoices)

### total
Total Amount of Invoice Payment (including any processing fees, etc.)

### currency
Currency Code for this Invoice Payment (eg `AUD`)

### paymentMethod
Payment Method Identifier (eg `paypal_express_3`) for this Invoice Payment

### payment_transactions
Oro Payment Transactions for this Invoice Payment

### createdAt
Date/Time when Invoice Payment was created (in UTC/Zulu time)

### updatedAt
Date/Time when Invoice Payment was last updated (in UTC/Zulu time)
