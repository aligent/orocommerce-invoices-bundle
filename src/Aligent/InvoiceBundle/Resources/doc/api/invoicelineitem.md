# Aligent\InvoiceBundle\Entity\InvoiceLineItem

## ACTIONS

### get

Retrieve a specific invoice line item.

{@inheritdoc}

### get_list

Retrieve a collection of invoice line items.

{@inheritdoc}

### create

Create a new invoice line item.

The created record is returned with the response.

{@inheritdoc}


### update

Edit a specific invoice line item.

The updated record is returned with the response.

{@inheritdoc}

### delete

Delete a specific invoice line item.

{@inheritdoc}

### delete_list

Delete a collection of invoice line items.

{@inheritdoc}

## FIELDS

### invoice
Invoice this Line Item belongs to

### amount
Total Amount for this Invoice Line Item

### currency
Currency Code for this Invoice Line Item (eg `AUD`)

### summary
Description of this Line Item (Customer-Facing, eg 'Product ABC')
