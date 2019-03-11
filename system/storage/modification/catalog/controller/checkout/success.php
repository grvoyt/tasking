<?php
class ControllerCheckoutSuccess extends Controller {
	public function index() {
		$this->load->language('checkout/success');
                /**/
                $this->load->model('checkout/order');
                $this->load->model('catalog/product');
                $this->load->model('checkout/sheet');
                require_once DIR_SYSTEM . '/sheet.php';
                $sheet = new sheet();
                
		if (isset($this->session->data['order_id'])) {

			// [BEGIN]

			if (true === $this->oc_smsc_init()) {
				if ($this->config->get('oc_smsc_customer_new_order')) {
					$o_i = $this->oc_smsc_gateway->get_order_info($this->config->get('oc_smsc_textarea_customer_new_order'), $this->session->data['order_id']);
					$this->oc_smsc_gateway->send($this->config->get('oc_smsc_login'), $this->config->get('oc_smsc_password'), $o_i['phone'],
												$o_i['message'], $this->config->get('oc_smsc_signature'),
												$this->config->get('oc_smsc_call_cust_order') ? 'call=1' : '');
				}

				if ($this->config->get('oc_smsc_admin_new_order')) {
					$o_i = $this->oc_smsc_gateway->get_order_info($this->config->get('oc_smsc_textarea_admin_new_order'), $this->session->data['order_id']);
					$this->oc_smsc_gateway->send($this->config->get('oc_smsc_login'), $this->config->get('oc_smsc_password'),
												$this->config->get('oc_smsc_telephone'), $o_i['message'], $this->config->get('oc_smsc_signature'),
												$this->config->get('oc_smsc_call_adm_order') ? 'call=1' : '');
				}
			}
			// [END]
		
			$this->cart->clear();
                        
                        $products = $this->model_catalog_product->getProducts();
                
                        foreach ($products as $key => $value) {

                            if($value['quantity'] <= 0 && $value['quantity_two'] <= 0){
                                $price = $value['price'];
                            }elseif($value['quantity'] > 0){
                                $price = $value['price'];
                            }elseif($value['quantity'] <= 0 && $value['quantity_two'] > 0){
                                $price = $value['price_two'];
                            }elseif($value['quantity'] <= 0 && $value['quantity_two'] <= 0 && $value['status_two'] == true){
                                $price = $value['price'];
                            }

                            $data[] = array(
                                $value['name'], $value['sku'], (int)$price
                            );
                        }

                        $sheet->clear();
                        $sheet->insertThree($data);
                        $this->session->data['sheet'] = $this->session->data['order_id'];
			// Add to activity log
			if ($this->config->get('config_customer_activity')) {
				$this->load->model('account/activity');

				if ($this->customer->isLogged()) {
					$activity_data = array(
						'customer_id' => $this->customer->getId(),
						'name'        => $this->customer->getFirstName() . ' ' . $this->customer->getLastName(),
						'order_id'    => $this->session->data['order_id']
					);

					$this->model_account_activity->addActivity('order_account', $activity_data);
				} else {
					$activity_data = array(
						'name'     => $this->session->data['guest']['firstname'] . ' ' . $this->session->data['guest']['lastname'],
						'order_id' => $this->session->data['order_id']
					);

					$this->model_account_activity->addActivity('order_guest', $activity_data);
				}
			}
                        
                        $product = $this->model_checkout_sheet->getOrderProduct($this->session->data['order_id']);

                        foreach ($product as $key => $value) {
                            $product_info[$key] = $this->model_checkout_sheet->getProduct($value['product_id']);
                            $product_info[$key]['quantity_int'] = $value['quantity'];
                            $product_info[$key]['total'] = $value['total'];
                            $product_info[$key]['order_status_id'] = $value['order_status_id'];
                            $product_info[$key]['status_name'] = $value['name'];
                            $product_info[$key]['price'] = $value['price'];
                        }
                        
                        foreach ($product_info as $v) {
                            
                            if($v['quantity'] <= 0 && $v['quantity_two'] <= 0){
                                $stock = "1";
                            }elseif($v['quantity'] > 0){
                                $stock = "1";
                            }elseif($v['quantity'] <= 0 && $v['quantity_two'] > 0){
                                $stock = "2";
                            }elseif($v['quantity'] <= 0 && $v['quantity_two'] <= 0 && $v['status_two'] == true){
                                $stock = "2";
                            }
                            
                            $data_order = array(
                                "order_id"          => (int)$this->session->data['order_id'],
                                "date_added"        => date('d.m.Y'),
                                "name"              => $v['name'],
                                "sku"               => $v['sku'],
                                "model"             => $v['model'],
                                "quantity"          => (int)$v['quantity'],
                                "quantity_two"      => (int)$v['quantity_two'],
                                "quantity_int"      => (int)$v['quantity_int'],
                                "status_two"        => "пох",
                                "telephone"         => $this->session->data['simple']['customer']['telephone'],
                                "email"             => $this->session->data['simple']['customer']['email'],
                                "comment"           => $this->session->data['simple']['comment'],
                                "order_status"      => $v['status_name'],
                                "firstname"         => $this->session->data['simple']['shipping_address']['firstname'],
                                "shipping_address_1"   => $this->session->data['simple']['shipping_address']['address_1'],
                                "shipping"          => $this->session->data['shipping_method']['title'],
                                "total"             => $v['total'],
                                "price"             => (int)$v['price'],
                                "stock"             => (int)$stock,
                            );

                            $sheet->insert($data_order);
                            //запускаем пересчет через 4 секунды
                            $params = array(
                            	'time'=>4,
                            	'token'=>md5('istylespb.ru'),
                            	'type' => 0
                            );
                            $this->exec_bg_script($params);
                        }
                        
			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
			unset($this->session->data['payment_method']);
			unset($this->session->data['payment_methods']);
			unset($this->session->data['guest']);
			unset($this->session->data['comment']);
			unset($this->session->data['order_id']);
			unset($this->session->data['coupon']);
			unset($this->session->data['reward']);
			unset($this->session->data['voucher']);
			unset($this->session->data['vouchers']);
			unset($this->session->data['totals']);
		}

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_basket'),
			'href' => $this->url->link('checkout/cart')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_checkout'),
			'href' => $this->url->link('checkout/checkout', '', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_success'),
			'href' => $this->url->link('checkout/success')
		);

		$data['heading_title'] = $this->language->get('heading_title');

		if ($this->customer->isLogged()) {
			$data['text_message'] = sprintf($this->language->get('text_customer'), $this->url->link('account/account', '', true), $this->url->link('account/order', '', true), $this->url->link('account/download', '', true), $this->url->link('information/contact'));
		} else {
			$data['text_message'] = sprintf($this->language->get('text_guest'), $this->url->link('information/contact'));
		}

		$data['button_continue'] = $this->language->get('button_continue');

		$data['continue'] = $this->url->link('common/home');
                
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');
		$data['testio'] = 'sdfsdf';
		$this->response->setOutput($this->load->view('common/success', $data));
                
	}

	public function exec_bg_script(array $args = [], $escape = true)
	{
	    $script = '/var/www/istylespb/data/www/istylespb.ru/status/script.php';
	    
	    if (($file = realpath($script)) === false) {
	        print_r('[exec_bg_script] File ' . $script . ' not found!');
	        return false;
	    }
	    array_walk($args, function(&$value, $key) use($escape) {
	        $value = $escape ? $key . '=' . escapeshellarg($value) : $key . '=' . $value;
	    });

	    $command = sprintf('php %s %s', $file, implode(' ', $args)) . " > /dev/null &";
	    exec($command);
	}
}