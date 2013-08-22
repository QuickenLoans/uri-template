<?php
/**
 * @copyright ©2005—2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\UriTemplate;

/**
 * @api
 */
class UriTemplate
{
    /**
     * @var Expander
     */
    private $expander;

    /**
     * @var string
     */
    private $tpl;

    /**
     * @param string $tpl
     * @param Expander $expander
     * @throws Exception
     */
    public function __construct($tpl, Expander $expander)
    {
        $expander($tpl, []);
        $error = $expander->lastError();
        if ($error) {
            throw new Exception($error);
        }
        $this->expander = $expander;
        $this->tpl = $tpl;
    }

    /**
     * @param array $variables
     * @throws Exception
     * @return string
     */
    public function expand(array $variables)
    {
        $result = call_user_func($this->expander, $this->tpl, $variables);

        if ($this->expander->lastError()) {
            throw new Exception($this->expander->lastError());
        }

        return $result;
    }
}
