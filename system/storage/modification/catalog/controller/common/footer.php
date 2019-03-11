<?php
ignore_user_abort(true);
class ControllerCommonFooter extends Controller {
	public function index() {
		$this->load->language('common/footer');

				$this->load->language('coloring/coloring');
				
				$this->load->model('setting/setting');
				$this->load->model('design/banner');
				$this->load->model('tool/image');

				$data['language_id'] = $this->config->get('config_language_id');
				
				$data['coloring'] = array();
				$data['coloring'] = $this->model_setting_setting->getSetting('coloring');
				
				$coloring = array();
				$coloring = $this->model_setting_setting->getSetting('coloring', $this->config->get('config_store_id'));
				
				$pay_icons_banner_id = -99;
				$data['pay_icons_toggle'] = false;
				$data['footer_map_toggle'] = false;
				$data['footer_map'] = array();

				if(!empty($coloring)){
					$pay_icons_banner_id = $coloring['t1_pay_icons_banner_id'];
					$data['pay_icons_toggle'] = $coloring['t1_pay_icons_toggle'];
					$data['footer_map_toggle'] = $coloring['t1_footer_map_toggle'];
					$data['footer_map'] = $coloring['t1_footer_map'];
				}
				
				$data['pay_icons'] = array();
				$pay_icons = $this->model_design_banner->getBanner($pay_icons_banner_id);
				
				foreach ($pay_icons as $pay_icon) {
					if (is_file(DIR_IMAGE . $pay_icon['image'])) {
						$data['pay_icons'][] = array(
							'title' => $pay_icon['title'],
							'link'  => $pay_icon['link'],
							'image' => $this->model_tool_image->resize($pay_icon['image'], 48, 32)
						);
					}
				}				
				
      

		$data['scripts'] = $this->document->getScripts('footer');

		$data['text_information'] = $this->language->get('text_information');
		$data['text_service'] = $this->language->get('text_service');
		$data['text_extra'] = $this->language->get('text_extra');
		$data['text_contact'] = $this->language->get('text_contact');
		$data['text_return'] = $this->language->get('text_return');
		$data['text_sitemap'] = $this->language->get('text_sitemap');
		$data['text_manufacturer'] = $this->language->get('text_manufacturer');
		$data['text_voucher'] = $this->language->get('text_voucher');
		$data['text_affiliate'] = $this->language->get('text_affiliate');
		$data['text_special'] = $this->language->get('text_special');
		$data['text_account'] = $this->language->get('text_account');
		$data['text_order'] = $this->language->get('text_order');
		$data['text_wishlist'] = $this->language->get('text_wishlist');
		$data['text_newsletter'] = $this->language->get('text_newsletter');

		$this->load->model('catalog/information');

		$data['informations'] = array();

		foreach ($this->model_catalog_information->getInformations() as $result) {
			if ($result['bottom']) {
				$data['informations'][] = array(
					'title' => $result['title'],
					'href'  => $this->url->link('information/information', 'information_id=' . $result['information_id'])
				);
			}
		}

		$data['contact'] = $this->url->link('information/contact');
		$data['return'] = $this->url->link('account/return/add', '', true);
		$data['sitemap'] = $this->url->link('information/sitemap');
		$data['manufacturer'] = $this->url->link('product/manufacturer');
		$data['voucher'] = $this->url->link('account/voucher', '', true);
		$data['affiliate'] = $this->url->link('affiliate/account', '', true);
		$data['special'] = $this->url->link('product/special');
		$data['account'] = $this->url->link('account/account', '', true);
		$data['order'] = $this->url->link('account/order', '', true);
		$data['wishlist'] = $this->url->link('account/wishlist', '', true);
		$data['newsletter'] = $this->url->link('account/newsletter', '', true);
        $data['sheet'] = isset($this->session->data['sheet']) ? true : false;
        $data['powered'] = sprintf($this->language->get('text_powered'), $this->config->get('config_name'), date('Y', time())).$this->language->get('theme_powered');
      

		// Whos Online
		if ($this->config->get('config_customer_online')) {
			$this->load->model('tool/online');

			if (isset($this->request->server['REMOTE_ADDR'])) {
				$ip = $this->request->server['REMOTE_ADDR'];
			} else {
				$ip = '';
			}

			if (isset($this->request->server['HTTP_HOST']) && isset($this->request->server['REQUEST_URI'])) {
				$url = 'http://' . $this->request->server['HTTP_HOST'] . $this->request->server['REQUEST_URI'];
			} else {
				$url = '';
			}

			if (isset($this->request->server['HTTP_REFERER'])) {
				$referer = $this->request->server['HTTP_REFERER'];
			} else {
				$referer = '';
			}

			$this->model_tool_online->addOnline($ip, $this->customer->getId(), $url, $referer);
		}

		return $this->load->view('common/footer', $data);
	}
        
        public function updateTable(){
            ignore_user_abort(true);/*
            if($this->request->post['res']){
                require_once DIR_SYSTEM . '/run2.php';
            }*/
            unset($this->session->data['sheet']);
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
        }
}
