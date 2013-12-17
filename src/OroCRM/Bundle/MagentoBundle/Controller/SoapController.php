<?php

namespace OroCRM\Bundle\MagentoBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\IntegrationBundle\Provider\AbstractConnector;

use OroCRM\Bundle\MagentoBundle\Provider\StoreConnector;
use OroCRM\Bundle\MagentoBundle\Entity\MagentoSoapTransport;

class SoapController extends Controller
{
    /**
     * @Route("/check")
     * @AclAncestor("oro_integration_channel_update")
     */
    public function checkAction(Request $request)
    {
        $transport = $this->get('orocrm_magento.soap_transport');

        /*
         * Transport setting entity should be set to form
         * in case when password should be merged from saved data
         */
        $data = null;
        if ($id = $request->get('id', false)) {
            $data = $this->get('doctrine.orm.entity_manager')->find($transport->getSettingsEntityFQCN(), $id);
        }

        $form = $this->get('form.factory')
            ->createNamed('soap-check', $transport->getSettingsFormType(), $data, ['csrf_protection' => false]);
        $form->submit($request);

        /** @var MagentoSoapTransport $transportEntity */
        $transportEntity      = $form->getData();
        $websites             = $supportedConnectors = [];
        $isExtensionInstalled = false;
        try {
            $result = $transport->init($transportEntity->getSettingsBag());
            if ($result) {
                $stores   = $transport->call(StoreConnector::ACTION_STORE_LIST);
                $websites = $this->get('orocrm_magento.converter.stores_to_website')->convert($stores);

                $isExtensionInstalled = $transport->call(StoreConnector::ACTION_PING);
                $isExtensionInstalled = !empty($isExtensionInstalled->version);

                if ($type = $request->get('type', false)) {
                    $connectors = $this->get('oro_integration.manager.types_registry')
                        ->getRegisteredConnectorsTypes($type);

                    if ($isExtensionInstalled) {
                        /*
                        $connectors = $connectors->filter(
                            function (AbstractConnector $item) use ($isExtensionInstalled) {
                                return $item->isExtensionRequired() && !$isExtensionInstalled;
                            }
                        );
                        */
                    }
                }
            }
        } catch (\Exception $e) {
            $result = false;
        }

        return new JsonResponse(
            [
                'success'              => $result,
                'websites'             => $websites,
                'isExtensionInstalled' => $isExtensionInstalled,
            ]
        );
    }
}
