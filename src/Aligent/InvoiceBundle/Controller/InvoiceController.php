<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\InvoiceBundle\Controller;

use Aligent\InvoiceBundle\Entity\Invoice;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class InvoiceController extends AbstractController
{
    /**
     * @Route("/", name="aligent_invoice_index")
     * @Template("AligentInvoiceBundle:AligentInvoice:index.html.twig")
     * @Acl(
     *      id="aligent_invoice_view",
     *      type="entity",
     *      class="AligentInvoiceBundle:AligentInvoice",
     *      permission="VIEW"
     * )
     * @return array<string,mixed>
     */
    public function indexAction(): array
    {
        return [
            'entity_class' => Invoice::class,
        ];
    }

    /**
     * @Route("/view/{id}", name="aligent_invoice_view", requirements={"id"="\d+"})
     * @Template("AligentInvoiceBundle:AligentInvoice:view.html.twig")
     *
     * @param Invoice $invoice
     * @return array<string,mixed>
     */
    public function viewAction(Invoice $invoice): array
    {
        return [
            'entity' => $invoice,
        ];
    }
}
