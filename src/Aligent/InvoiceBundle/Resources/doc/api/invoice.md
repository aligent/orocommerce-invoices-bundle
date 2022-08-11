# Aligent\InvoiceBundle\Entity\Invoice

## ACTIONS

### get

Retrieve a specific invoice.

{@inheritdoc}

### get_list

Retrieve a collection of invoices.

{@inheritdoc}

### create

Create a new invoice.

The created record is returned with the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
    "data": {
        "type": "aligentinvoices",
        "id": "new-invoice-'1",
        "attributes": {
            "invoiceNo": "INV-413",
            "issueDate": "2022-06-01",
            "dueDate": "2022-07-01",
            "amount": "100.0000",
            "currency": "AUD",
            "totalTax": "11.11",
            "amountPaid": "80.0000",
            "createdAt": "2022-06-01T16:15:20Z",
            "updatedAt": "2022-06-14T02:10:24Z"
        },
        "relationships": {
            "customer": {
                "data": {
                    "type": "customers",
                    "id": "7"
                }
            },
            "lineItems": {
                "data": [
                    {
                        "type": "aligentinvoicelineitems",
                        "id": "line-item-1"
                    },
                    {
                        "type": "aligentinvoicelineitems",
                        "id": "line-item-2"
                    }
                ]
            },
            "status": {
                "data": {
                    "type": "aligentinvoicestatuses",
                    "id": "open"
                }
            }
        }
    },
    "included": [
        {
            "type": "aligentinvoicelineitems",
            "id": "line-item-1",
            "attributes": {
                "amount": "25.0000",
                "currency": "AUD",
                "summary": "Line Item 1"
            }
        },
        {
            "type": "aligentinvoicelineitems",
            "id": "line-item-2",
            "attributes": {
                "amount": "75.0000",
                "currency": "AUD",
                "summary": "Line Item 2"
            }
        }
    ]
}
```
{@/request}

### update

Edit a specific invoice.

The updated record is returned with the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
    "data": {
        "type": "aligentinvoices",
        "id": "2",
        "attributes": {
            "dueDate": "2022-08-01",
            "amountPaid": "10.0000"
        },
        "relationships": {
            "status": {
                "data": {
                    "type": "aligentinvoicestatuses",
                    "id": "open"
                }
            }
        }
    }
}
```
{@/request}

### delete

Delete a specific invoice.

{@inheritdoc}

### delete_list

Delete a collection of invoices.

{@inheritdoc}

## FIELDS

### invoiceNo
Unique Identifier for this Invoice (Customer-Facing), eg `INV-045`

### customer
Customer which owns this Invoice

### issueDate
Date in which Invoice was issued

### dueDate
Date after which Invoice will be flagged as 'Overdue'

### amount
Total Amount (inc Tax) for this Invoice

### currency
Currency Code for this Invoice (eg `AUD`)

### totalTax
Total Amount of Tax for this Invoice

### amountPaid
Total Amount paid against this Invoice

### lineItems
Line Items for this Invoice

### status
Status of this Invoice (eg Open, Paid, Overdue, Cancelled)
