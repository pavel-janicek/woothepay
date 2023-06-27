<?php
require_once implode(DIRECTORY_SEPARATOR, array(__DIR__, '..', 'TpUtils.php'));

// …everything else can be loaded using TpUtils::requirePaths.
TpUtils::requirePaths(array(
	array('helpers', 'TpMerchantHelper.php'),
	array('TpEscaper.php')
));

class TpUrlHelper extends TpMerchantHelper {
  /**
	 * Button link target URL.
	 *
	 * @return string
	 */
	public function buildUrl() {
		$gateUrl = $this->payment->getMerchantConfig()->gateUrl;
		$query   = $this->buildQuery();
		if(is_null($this->payment->getMethodId())) {
			$query .= '&methodSelectionAllowed';
		}
		return $gateUrl . '?' . $query;
	}
  public function getRedirectUrl(){
    $targetUrl = self::buildUrl();
    return $targetUrl;
  }
	function render() {
		$url = $this->payment->getMerchantConfig()->gateUrl;
		$queryArgs = array_filter(array(
			'skin' => $this->skin
		));

		$out = "";
		if(!$this->disableButtonCss) {
			$skin = $this->skin == "" ? "" : "/$this->skin";
			$href = "{$url}div/style$skin/div.css?v=" . time();
			$href = TpEscaper::htmlEntityEncode($href);
			$out .= "<link href=\"$href\" type=\"text/css\" rel=\"stylesheet\" />\n";
		}

		$thepayGateUrl = $url.'div/index.php?'.$this->buildQuery($queryArgs);
		$thepayGateUrl = TpEscaper::jsonEncode($thepayGateUrl);
		$disableThepayPopupCss = TpEscaper::jsonEncode($this->disablePopupCss);
		$out .= "<script type=\"text/javascript\">";
		$out .= "\tvar thepayGateUrl = $thepayGateUrl,\n";
		$out .= "\t\tdisableThepayPopupCss = $disableThepayPopupCss;\n";
		$out .= "</script>\n";

		$src = "{$url}div/js/jquery.js?v=" . time();
		$src = TpEscaper::htmlEntityEncode($src);
		$out .= "<script type=\"text/javascript\" src=\"$src\" async=\"async\"></script>\n";

		$src = "{$url}div/js/div.js?v=" . time();
		$src = TpEscaper::htmlEntityEncode($src);
		$out .= "<script type=\"text/javascript\" src=\"$src\" async=\"async\"></script>\n";

		$out .= "<div id=\"thepay-method-box\" style=\"border: 0;\"></div>\n";
		return $out;
	}
}
