<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Shell
 * @copyright   Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
require_once 'abstract.php';

/**
 * Magento XML-RPC call to OpenERP
 *
 * @category    Mage
 * @package     Mage_Shell
 * @author      Cyril MORISSE - Audaxis  <cmo@audaxis.com>
 */
class Mage_Shell_XMLRPC_Tester extends Mage_Shell_Abstract
{
    /**
     * Run script
     *
     */
    public function run()
    {
        $OPENERP_SERVER_HOST= "localhost";
        $OPENERP_SERVER_PORT= "8069";
        $OPENERP_USERNAME   = "admin";
        $OPENERP_PASSWORD   = "velo=615";
        $OPENERP_DATABASE   = "v2_eo_mono_s41";

        /******
         * step 1 : login
         * à partir d'un user / OPENERP_PASSWORD on récupère un user id ou False
         * si login à échoué
         */
        $client =  new Zend_XmlRpc_Client("http://${OPENERP_SERVER_HOST}:${OPENERP_SERVER_PORT}/xmlrpc/common");
        $request = new Zend_XmlRpc_Request();
        $request->setMethod('login');
        $params = array(
            $OPENERP_DATABASE,
            $OPENERP_USERNAME,
            $OPENERP_PASSWORD
        );
        $request->setParams($params);
        $client->doRequest($request);
        $response = $client->getLastResponse();
        $uid = $response->getReturnValue();
        if (! $uid ) {
            echo "Mauvais utilisateur ou mot de passe.\n";
            return;
        }

        /*****
         * Step 2 : On cherche tous les shops (on récupère une liste d'ids).
         */
        $client_object =  new Zend_XmlRpc_Client("http://${OPENERP_SERVER_HOST}:${OPENERP_SERVER_PORT}/xmlrpc/object");
        $request->setMethod('execute');
        $params = array(
            $OPENERP_DATABASE,      // OPENERP_DATABASE
            $uid,           // !!!! uid pas OPENERP_USERNAME
            $OPENERP_PASSWORD,
            "sale.shop",    // object
            "search",       // methode
            array()         // search criterion: array() = aucun = on les récupère tous
        );
        $request->setParams($params);
        $client_object->doRequest($request);
        $response = $client_object->getLastResponse();
        $shop_array_ids = $response->getReturnValue(); // contient maintenant la liste de tous les shop

        /*****
         * step 3
         * A partir de la liste d'ids des shops, on récupère les infos
         * que l'on ajoute dans $shop_array
         */
        $shop_array = array();
        foreach($shop_array_ids as $shop_id) {
            $request->setMethod('execute');
            $params = array(
                $OPENERP_DATABASE,  // OPENERP_DATABASE
                $uid,              // !!!! uid not OPENERP_USERNAME
                $OPENERP_PASSWORD,
                "sale.shop",       // object
                "read",            // method
                $shop_id,          // $is de la boutique dont on veut les informations
                null               // liste des champs : null = tous
            );
            $request->setParams($params);
            $client_object->doRequest($request);
            $response = $client_object->getLastResponse();
            $shop_array[] = $response->getReturnValue();
        }

        echo count($shop_array) . " shops trouvés:\n";
        foreach( $shop_array as $shop ) {
            echo "- " . $shop['name'] . "\n";
        }
    }

    /**
     * Retrieve Usage Help Message
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f xmlrpc.php -- [options]
  help                         Print this help

USAGE;
    }
}

ini_set('display_errors', '1');     // Needed for my MAMP Configuration
$shell = new Mage_Shell_XMLRPC_Tester();
$shell->run();
