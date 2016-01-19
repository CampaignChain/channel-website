<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\Channel\WebsiteBundle\Controller;

use CampaignChain\CoreBundle\Entity\Location;
use CampaignChain\CoreBundle\Util\ParserUtil;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Url;

class WebsiteController extends Controller
{
    public function newAction(Request $request)
    {
        $locationType = $this->get('campaignchain.core.form.type.location');
        $locationType->setBundleName('campaignchain/location-website');
        $locationType->setModuleIdentifier('campaignchain-website');

        $form = $this->createFormBuilder()
            ->add('URL', 'url', array(
                'label' => 'Website URL',
                'constraints' => array(
                    new Url(array(
                        'checkDNS'  => true,
                    )),
                )))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $locationURL = $form->getData()['URL'];
            $locationName = ParserUtil::getHTMLTitle($locationURL, $locationURL);
            $locationService = $this->get('campaignchain.core.location');
            $locationModule = $locationService->getLocationModule('campaignchain/location-website', 'campaignchain-website');

            $location = new Location();
            $location->setLocationModule($locationModule);
            $location->setName($locationName);
            $location->setUrl($locationURL);

            // Get the Website's favicon as Channel image if possible.
            $favicon = ParserUtil::getFavicon($locationURL);
            if($favicon){
                $locationImage = $favicon;
            } else {
//                $locationImage = $this->container->get('templating.helper.assets')
//                    ->getUrl(
//                        'bundles/campaignchainlocationwebsite/images/icons/256x256/website.png',
//                        null
//                    );
                $locationImage = null;
            }
            $location->setImage($locationImage);

            $wizard = $this->get('campaignchain.core.channel.wizard');
            $wizard->setName($location->getName());

            $repository = $this->getDoctrine()
                ->getRepository('CampaignChainCoreBundle:Location');
            if(!$repository->findBy(array('url' => $location->getUrl())))
            {
                $wizard->addLocation($location->getUrl(), $location);
                try {
                    $channel = $wizard->persist();
                    $wizard->end();
                    $this->get('session')->getFlashBag()->add(
                        'success',
                        "The Website '" . $location->getUrl() . "' has been connected."
                    );
                    return $this->redirect($this->generateUrl(
                        'campaignchain_core_channel'));


                } catch(\Exception $e) {
                    $this->addFlash('warning',
                        "An error occured during the creation of the website location");
                    $this->get('logger')->addError($e->getMessage());
                }
            }
            else{
                $this->addFlash('warning',
                    "The website  '" . $location->getUrl() . "' already exists.");
            }
            }







            //return $this->redirect($this->generateUrl(
              //  'campaignchain_channel_website_page_new',
                //array('id' => $channel->getLocations()[0]->getId())));



        return $this->render(
            'CampaignChainCoreBundle:Base:new.html.twig',
            array(
                'page_title' => 'Connect Website',
                'form' => $form->createView(),
            ));
    }

/*    public function newPageAction(Request $request, $id)
    {
        $website = $this->getDoctrine()
            ->getRepository('CampaignChainCoreBundle:Location')
            ->find($id);

        if (!$website) {
            throw new \Exception(
                'No Location found for id '.$id
            );
        }

        $form = $this->createFormBuilder()
            ->add('pages', 'bootstrap_collection', array(
                'label' => 'Pages',
                    'allow_add'          => true,
                    'allow_delete'       => true,
                    'add_button_text'    => 'Add URL',
                    'delete_button_text' => 'Delete URL',
                'attr' => array('help_text' => 'Add URLs to various pages within the Website.')
            ))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $pages = $form->getData()['pages'];
            if(count($pages)){
                $constraint = new Url();
                $errors = array();

                $locationService = $this->get('campaignchain.core.location');
                $locationModule = $locationService->getLocationModule('campaignchain/location-website', 'campaignchain-website-page');

                foreach($pages as $pageURL){
                    $constraint->message = "'".$pageURL."' is not a valid URL.";
                    $errors = $this->get('validator')->validateValue(
                        $pageURL,
                        $constraint
                    );
                    if(count($errors)){
                        $this->get('session')->getFlashBag()->add(
                            'warning',
                            $errors[0]->getMessage()
                        );
                        break;
                    }

                    // Ensure that the page URL resides on the Website
                    if(strpos($pageURL, $website->getUrl()) !== false){
                        // Get the page title
                        $pageTitle = ParserUtil::getHTMLTitle($website->getUrl(), $pageURL);
                    } else {
                        $this->get('session')->getFlashBag()->add(
                            'warning',
                            "The page '".$pageURL."' does not reside in Website '".$website->getUrl()."'."
                        );
                        break;
                    }

                    // Persist the new Location.
                    $location = new Location();
                    $location->setLocationModule($locationModule);
                    $location->setName($pageTitle);
                    $location->setUrl($pageURL);
                    $location->setImage(
                        $this->container->get('templating.helper.assets')
                            ->getUrl(
                                'bundles/campaignchainlocationwebsite/images/icons/256x256/page.png',
                                null
                            )
                    );

                    $repository = $this->getDoctrine()->getManager();
                    $repository->persist($location);
                }

                if(count($errors) === 0){
                    $repository->flush();

                    $this->get('session')->getFlashBag()->add(
                        'success',
                        "The Website '".$website->getUrl()."' and related pages have been connected."
                    );
                }
            }

            return $this->redirect($this->generateUrl(
                'campaignchain_core_channel'));
        }

        return $this->render(
            'CampaignChainCoreBundle:Location:new.html.twig',
            array(
                'page_title' => 'Add Website Pages',
                'form' => $form->createView(),
                'form_submit_label' => 'Save',
                'location' => $website,
            ));
    }*/
}
