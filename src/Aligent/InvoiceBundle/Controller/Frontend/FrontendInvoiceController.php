<?php
/**
 * @category  Aligent
 * @author    Bruno Pasqualini <bruno.pasqualini@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\InvoiceBundle\Controller\Frontend;

use Aligent\InvoiceBundle\Entity\Invoice;
use Aligent\InvoiceBundle\Entity\InvoicePayment;
use Aligent\InvoiceBundle\Factory\InvoicePaymentFactory;
use Aligent\InvoiceBundle\Form\Handler\InvoicePaymentFormHandler;
use Aligent\InvoiceBundle\Form\Type\InvoicePaymentType;
use Aligent\InvoiceBundle\Manager\PaymentManager;
use Aligent\InvoiceBundle\Provider\FrontendInvoiceProvider;
use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FrontendInvoiceController extends AbstractController
{
    protected UpdateHandlerFacade $updateHandler;
    protected FrontendInvoiceProvider $frontendInvoiceProvider;
    protected InvoicePaymentFactory $invoicePaymentFactory;
    protected InvoicePaymentFormHandler $invoicePaymentFormHandler;
    protected PaymentManager $paymentManager;

    public function __construct(
        UpdateHandlerFacade $updateHandler,
        FrontendInvoiceProvider $frontendInvoiceProvider,
        InvoicePaymentFactory $invoicePaymentFactory,
        InvoicePaymentFormHandler $invoicePaymentFormHandler,
        PaymentManager $paymentManager,
    ) {
        $this->updateHandler = $updateHandler;
        $this->frontendInvoiceProvider = $frontendInvoiceProvider;
        $this->invoicePaymentFactory = $invoicePaymentFactory;
        $this->invoicePaymentFormHandler = $invoicePaymentFormHandler;
        $this->paymentManager = $paymentManager;
    }

    /**
     * @Route("/", name="aligent_invoice_frontend_index")
     * @Layout(vars={"entity_class"})
     * @Acl(
     *      id="aligent_invoice_frontend_view",
     *      type="entity",
     *      class="AligentInvoiceBundle:Invoice",
     *      permission="VIEW",
     *      group_name="commerce"
     * )
     * @return array<string,mixed>
     */
    public function indexAction(): array
    {
        // Must be logged in
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        return [
            'entity_class' => Invoice::class,
        ];
    }

    /**
     * @Route("/view/{id}", name="aligent_invoice_frontend_view", requirements={"id"="\d+"})
     * @Layout()
     * @AclAncestor("aligent_invoice_frontend_view")
     *
     * @param Invoice $invoice
     * @return array<string,mixed>
     */
    public function viewAction(Invoice $invoice): array
    {
        // Must be logged in
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        return [
            'data' => [
                'invoice' => $invoice,
            ],
        ];
    }

    /**
     * paymentAction - Create or load a Payment for one/more Invoices
     *
     * @Route("/payment/create", name="aligent_invoice_frontend_payment_create")
     * @Acl(
     *      id="aligent_frontend_invoice_payment_create",
     *      type="entity",
     *      class="AligentInvoiceBundle:InvoicePayment",
     *      permission="CREATE",
     *      group_name="commerce"
     * )
     * @return array<string,string>|RedirectResponse|JsonResponse
     */
    public function createPaymentAction(
        Request $request,
    ): array|RedirectResponse|JsonResponse {
        // Must be logged in
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        try {
            $invoicePayment = $this->invoicePaymentFactory->createFromUnpaidInvoices(true);
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('aligent_invoice_frontend_index');
        }

        return $this->redirectToRoute('aligent_invoice_frontend_payment', [
            'id' => $invoicePayment->getId(),
        ]);
    }

    /**
     * paymentAction - Create a Payment for one/more Invoices
     *
     * @Route("/payment/{id}", name="aligent_invoice_frontend_payment", requirements={"id"="\d+"})
     * @Layout()
     * @AclAncestor("aligent_frontend_invoice_payment_create")
     * @return array<string,string>|RedirectResponse|JsonResponse
     */
    public function paymentAction(
        Request $request,
        InvoicePayment $invoicePayment
    ): array|RedirectResponse|JsonResponse {
        // Must be logged in
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        if (!$invoicePayment->isActive()) {
            // Prevent complete Payments from being modified/reused
            $this->addFlash('error', 'aligent.invoice.frontend.payment.inactive.message');

            return $this->redirectToRoute('aligent_invoice_frontend_index');
        }

        /**
         * Making a Payment requires the InvoicePaymentFormHandler
         * to actually capture/process the Payment
         */
        return $this->update($invoicePayment, $request, $this->invoicePaymentFormHandler);
    }

    /**
     * @Route("/payment/save_state/{id}", name="aligent_invoice_frontend_payment_save_state", requirements={"id"="\d+"})
     * @AclAncestor("aligent_frontend_invoice_payment_create")
     * @param Request $request
     * @param InvoicePayment $invoicePayment
     * @return JsonResponse
     */
    public function saveStateAction(Request $request, InvoicePayment $invoicePayment): JsonResponse
    {
        $form = $this->createForm(InvoicePaymentType::class, $invoicePayment);

        $update = $this->frontendInvoiceProvider->createInvoicePaymentFormUpdate(
            $invoicePayment,
            $form
        );

        if ($update->handle($request)) {
            return new JsonResponse(['success' => true]);
        }

        return new JsonResponse([
            'success' => false,
            'message' => (string)$form->getErrors()
        ], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @param InvoicePayment $invoicePayment
     * @param Request $request
     * @param FormHandlerInterface|null $formHandler
     * @return array<string,array<string,mixed>>|RedirectResponse|JsonResponse
     */
    protected function update(
        InvoicePayment $invoicePayment,
        Request $request,
        FormHandlerInterface $formHandler = null,
    ): array|RedirectResponse|JsonResponse {
        $form = $this->createForm(InvoicePaymentType::class, $invoicePayment);

        $result = $this->updateHandler->update(
            $invoicePayment,
            $form,
            '', // This needs to be empty to prevent saveState from triggering a flash message
            $request,
            $formHandler,
        );

        $errors = [];

        if ($form->isSubmitted() && !$form->isValid()) {
            $formErrors = $form->getErrors();

            foreach ($formErrors as $error) {
                $errors[] = $error->getMessage();
            }
        }

        // If the payment manager has a response use that
        // Or if the form failed validation then display the errors
        if ($this->paymentManager->hasResponse()) {
            return new JsonResponse([
                'responseData' => $this->paymentManager->getResponse()
            ]);
        } elseif (count($errors) > 0) {
            return new JsonResponse([
                'errors' => $errors
            ]);
        }

        return $result instanceof Response
            ? $result
            : [
                'data' => [
                    'entity' => $invoicePayment,
                    'formView' => $form->createView()
                ]
            ];
    }

    /**
     * @Route("/success/{id}", name="aligent_invoice_frontend_payment_success", requirements={"id"="\d+"})
     * @AclAncestor("aligent_frontend_invoice_payment_create")
     * @param InvoicePayment $payment
     * @return RedirectResponse
     */
    public function successAction(InvoicePayment $payment): RedirectResponse
    {
        $this->addFlash('success', 'aligent.invoice.frontend.payment.success.message');

        return $this->redirectToRoute('aligent_invoice_frontend_index');
    }

    /**
     * @Route("/error/{id}", name="aligent_invoice_frontend_payment_error", requirements={"id"="\d+"})
     * @AclAncestor("aligent_frontend_invoice_payment_create")
     * @param InvoicePayment $payment
     * @return RedirectResponse
     */
    public function errorAction(InvoicePayment $payment): RedirectResponse
    {
        $this->addFlash('success', 'aligent.invoice.frontend.payment.failed.message');

        return $this->redirectToRoute('aligent_invoice_frontend_payment', ['id' => $payment->getId()]);
    }
}
