<?php
/**
 *
 *          ..::..
 *     ..::::::::::::..
 *   ::'''''':''::'''''::
 *   ::..  ..:  :  ....::
 *   ::::  :::  :  :   ::
 *   ::::  :::  :  ''' ::
 *   ::::..:::..::.....::
 *     ''::::::::::::''
 *          ''::''
 *
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to servicedesk@tig.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact servicedesk@tig.nl for more information.
 *
 * @copyright   Copyright (c) Total Internet Group B.V. https://tig.nl/copyright
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */

namespace TIG\PersistentShoppingCart\Model;

use Magento\Framework\Model\AbstractModel as FrameworkAbstractModel;
use Magento\Framework\Session\Config\ConfigInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use TIG\PersistentShoppingCart\Helper\Data as Helper;

abstract class AbstractModel extends FrameworkAbstractModel
{
    /**
     * @var string
     */
    protected $cookieName = 'shopping_cart_cookie';

    /**
     * @var \Magento\Framework\Session\Config\ConfigInterface $sessionConfig
     */
    protected $sessionConfig;

    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager
     */
    protected $cookieManager;

    /**
     * @var \Magento\Framework\Stdlib\Cookie\CookieMetadata|\Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadata
     */
    protected $cookieMetadata;

    /**
     * @var \TIG\PersistentShoppingCart\Helper\Data $helper
     */
    protected $helper;

    /**
     * AbstractModel constructor.
     *
     * @param \Magento\Framework\Session\Config\ConfigInterface $sessionConfig
     * @param \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager
     * @param \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadata
     * @param \TIG\PersistentShoppingCart\Helper\Data $helper
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        ConfigInterface                                         $sessionConfig,
        CookieManagerInterface                                  $cookieManager,
        CookieMetadataFactory                                   $cookieMetadata,
        Helper                                                  $helper,
        \Magento\Framework\Model\Context                        $context,
        \Magento\Framework\Registry                             $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb           $resourceCollection = null,
        array $data = []
    ) {
        $this->sessionConfig  = $sessionConfig;
        $this->cookieManager  = $cookieManager;
        $this->cookieMetadata = $cookieMetadata;
        $this->helper         = $helper;

        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection
        );
    }

    /**
     * Actions to use cookie data.
     *
     * @return \TIG\PersistentShoppingCart\Model\AbstractModel $this
     */
    public abstract function readCookie();

    /**
     * Update cookie with latest data.
     *
     * @return \TIG\PersistentShoppingCart\Model\AbstractModel $this
     */
    public abstract function writeCookie();

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    public function removeCookie()
    {
        $cookie = $this->cookieManager;

        if ($cookie->getCookie($this->cookieName)) {
            $cookie->deleteCookie($this->cookieName);
        }

        return $this;
    }

    /**
     * @return null|string
     */
    protected function _readCookie()
    {
        $value = $this->cookieManager->getCookie($this->cookieName);

        return isset($value) ? (string) $value : null;
    }

    /**
     * First check if we're allowed to create the cookie, if so, start.
     *
     * @param $value
     *
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    protected function _updateCookie($value)
    {
        if ($this->helper->isCookieRestricted()) {
            return;
        }

        if ($this->cookieManager->getCookie($this->cookieName) !== $value) {
            $this->processClientCookie($value);
        }
    }

    /**
     * Create Cookie metadata and if $value is set, set cookie. Otherwise, delete cookie.
     *
     * Uses Cookie Lifetime settings within web/cookie/cookie_restriction_lifetime.
     *
     * @param $value
     *
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    protected function processClientCookie($value)
    {
        $metadata = $this->cookieMetadata
            ->createPublicCookieMetadata()
            ->setDuration($this->sessionConfig->getCookieLifetime())
            ->setPath($this->sessionConfig->getCookiePath())
            ->setDomain($this->sessionConfig->getCookieDomain());

        if (isset($value)) {
            $this->cookieManager->setPublicCookie($this->cookieName, $value, $metadata);

            return;
        }

        $this->cookieManager->deleteCookie($this->cookieName);
    }
}
