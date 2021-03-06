<?php

class Controller_Payment extends Controller_Authenticate
{
	public function action_index()
	{
		// filter by open invoice receipts
		$data['payment_receipts'] = Model_Accounts_Payment_Receipt::find(
                                        'all', 
                                        array(
                                            // 'where' =>  array(
											// 	array('deleted_at', '<>', null)
											// ), 
                                            'order_by' => array('receipt_number' => 'desc'), 
                                            'limit' => 1000)
                                        );
		$this->template->title = "Receipts";
		$this->template->content = View::forge('accounts/payment/receipt/index', $data);
	}

	public function action_view($id = null)
	{
		is_null($id) and Response::redirect('accounts/sales-receipt');

		if ( ! $data['payment_receipt'] = Model_Accounts_Payment_Receipt::find($id))
		{
			Session::set_flash('error', 'Could not find cash receipt #'.$id);
			Response::redirect('accounts/sales-receipt');
		}
		$this->template->title = "Receipts";
		$this->template->content = View::forge('accounts/payment/receipt/view', $data);
	}

	public function action_create($bill_id = null)
	{
		if ($bill = Model_Sales_Invoice::find($bill_id))
			$this->template->set_global('bill', $bill, false);

		if (Input::method() == 'POST')
		{
			$val = Model_Accounts_Payment_Receipt::validate('create');
			if ($val->run())
			{
                // upload and save the file
				$file = Filehelper::upload();
                // Debug::dump($file); exit;
                if (!empty($file['saved_as']))
                    $payment_receipt->attachment = 'uploads'.DS.$file['name'];
                
				$payment_receipt = Model_Accounts_Payment_Receipt::forge(array(
					'receipt_number' => Input::post('receipt_number'),
					'type' => Input::post('type'),
					'source' => Input::post('source'),
					'source_id' => Input::post('source_id'),
					'date' => Input::post('date'),
					'payer' => Input::post('payer'),
					'payment_method' => Input::post('payment_method'),
					'reference' => Input::post('reference'),
					'status' => Input::post('status'),
					'gl_account_id' => Input::post('gl_account_id'),
					'amount' => Input::post('amount'),
					'tax_id' => Input::post('tax_id'),
					'bank_account_id' => Input::post('bank_account_id'),
					'description' => Input::post('description'),
					'fdesk_user' => Input::post('fdesk_user'),
				));

				try {
					if ($payment_receipt and $payment_receipt->save())
					{
						// update Order/Invoice and Guest Card
						if ($payment_receipt->source == Model_Sales_Invoice::INVOICE_SOURCE_LEASE)
							$bill = Model_Lease::find($payment_receipt->source_id);
						if ($payment_receipt->source == Model_Sales_Invoice::INVOICE_SOURCE_BOOKING)
							$bill = Model_Facility_Booking::find($payment_receipt->source_id);
						if ($payment_receipt->source == Model_Sales_Invoice::INVOICE_SOURCE_INVOICE)
						{
							$bill = Model_Sales_Invoice::find($payment_receipt->source_id);
							Model_Accounts_Payment_Receipt::updateInvoiceSettlement($bill, $payment_receipt->amount);
						}
						// Check the source/type and update accordingly
						Session::set_flash('success', 'Added cash receipt #'.$payment_receipt->receipt_number.'.');
						Response::redirect('accounts/payment/receipt/view/'.$payment_receipt->id);
					}
				}
				catch (Fuel\Core\Database_Exception $e)
				{
					Session::set_flash('error', $e->getMessage());
				}
			}
			else
			{
				Session::set_flash('error', $val->error());
			}
		}
		$this->template->title = "Receipts";
		$this->template->content = View::forge('accounts/payment/receipt/create');
	}

	public function action_edit($id = null)
	{
		is_null($id) and Response::redirect('accounts/sales-receipt');

		if ( ! $payment_receipt = Model_Accounts_Payment_Receipt::find($id))
		{
			Session::set_flash('error', 'Could not find cash receipt #'.$id);
			Response::redirect('accounts/sales-receipt');
		}

		$val = Model_Accounts_Payment_Receipt::validate('edit');
		if ($val->run())
		{
			$payment_receipt->receipt_number = Input::post('receipt_number');
			$payment_receipt->type = Input::post('type');
			$payment_receipt->source = Input::post('source');
			$payment_receipt->source_id = Input::post('source_id');
			$payment_receipt->date = Input::post('date');
			$payment_receipt->payer = Input::post('payer');
			$payment_receipt->payment_method = Input::post('payment_method');
			$payment_receipt->reference = Input::post('reference');
			$payment_receipt->status = Input::post('status');
			$payment_receipt->gl_account_id = Input::post('gl_account_id');
			$payment_receipt->amount = Input::post('amount');
			$payment_receipt->tax_id = Input::post('tax_id');
			$payment_receipt->bank_account_id = Input::post('bank_account_id');
			$payment_receipt->description = Input::post('description');
			$payment_receipt->fdesk_user = Input::post('fdesk_user');

			if ($payment_receipt->save())
			{
				Session::set_flash('success', 'Updated cash receipt #' . $payment_receipt->receipt_number);
				Response::redirect('accounts/sales-receipt');
			}
			else
			{
				Session::set_flash('error', 'Could not update cash receipt #' . $id);
			}
		}
		else
		{
			if (Input::method() == 'POST')
			{
				$payment_receipt->receipt_number = $val->validated('receipt_number');
				$payment_receipt->type = $val->validated('type');
				$payment_receipt->source = $val->validated('source');
				$payment_receipt->source_id = $val->validated('source_id');
				$payment_receipt->date = $val->validated('date');
				$payment_receipt->payer = $val->validated('payer');
				$payment_receipt->payment_method = $val->validated('payment_method');
				$payment_receipt->reference = $val->validated('reference');
				$payment_receipt->status = $val->validated('status');
				$payment_receipt->gl_account_id = $val->validated('gl_account_id');
				$payment_receipt->amount = $val->validated('amount');
				$payment_receipt->tax_id = $val->validated('tax_id');
				$payment_receipt->bank_account_id = $val->validated('bank_account_id');
				$payment_receipt->description = $val->validated('description');
				$payment_receipt->fdesk_user = $val->validated('fdesk_user');

				Session::set_flash('error', $val->error());
			}
			$this->template->set_global('payment_receipt', $payment_receipt, false);
		}
		$this->template->title = "Receipts";
		$this->template->content = View::forge('accounts/payment/receipt/edit');
	}

	public function action_delete($id = null)
	{
		is_null($id) and Response::redirect('accounts/sales-receipt');

		if (Input::method() == 'POST')
		{		
			if ($payment_receipt = Model_Accounts_Payment_Receipt::find($id))
			{
				// prepare transaction reversal amount
				$reverse_amount = -1 * $payment_receipt->amount;
				// unset the receipt amount
				$payment_receipt->amount = 0;
				if ($payment_receipt->save()) // save to preserve audit trail or soft_delete after save
				{
					// update Invoice and Guest Card
					Model_Accounts_Payment_Receipt::updateInvoiceSettlement($payment_receipt->invoice, $reverse_amount);
				}
				//if (is_null(Model_Accounts_Payment_Receipt::find($id)))
					// updateInvoiceSettlement
				Session::set_flash('success', 'Canceled cash receipt #'.$payment_receipt->receipt_number);
			}
			else
			{
				Session::set_flash('error', 'Could not cancel cash receipt #'.$id);
			}
		}
		else
		{
			Session::set_flash('error', 'Delete is not allowed');
		}
		Response::redirect('accounts/sales-receipt');
	}

	public function action_to_print($id)
	{
		$data['receipt'] = Model_Accounts_Payment_Receipt::find($id);

		$view = View::forge('template_print');
		$view->title = 'Receipt';
		$view->content = View::forge('document/payment_receipt', $data);

		return new Response($view);
	}

	public function action_get_source_list_options()
	{
		$type = Input::post('type');
		$listOptions = [];

        if (Input::is_ajax());
			switch ($type)
			{
				case 'Advance':
					$listOptions = array('Lease' => 'Lease', 'Booking' => 'Booking');
				break;
				default: // 'Settlement'
					$listOptions = array('Invoice' => 'Invoice');
			}
        
        return json_encode($listOptions);
	}

	public function action_get_source_ref_list_options()
	{
		$source = Input::post('source');
		$listOptions = [];

        if (Input::is_ajax());
			switch ($source)
			{
				case 'Lease': 
					$listOptions = Model_Lease::listOptions();
				break;
				case 'Booking':
					$listOptions = Model_Facility_Booking::listOptions();
				break;
				default: // Invoice
					$listOptions = Model_Sales_Invoice::listOptions();
			}
        
        return json_encode($listOptions);
	}

	public function action_get_source_info()
	{
		$source = Input::post('source');
		$source_id = Input::post('source_id');

		$data = [];

        if (Input::is_ajax());
			switch ($source)
			{
				case 'Lease': 
					$lease = Model_Lease::find($source_id);
					$data['customer_name'] = $lease->tenant->customer_name; // payer
					$data['amount'] = $lease->billed_amount;
				break;
				case 'Booking':
					$booking = Model_Facility_Booking::find($source_id);
					// $data['customer_name'] = $booking->customer->customer_name;
					$data['customer_name'] = '';
					$data['amount'] = $booking->total_amount;
				break;
				default: // 'Invoice'
					$invoice = Model_Sales_Invoice::find($source_id);
					$data['customer_name'] = $invoice->customer_name;
					$data['amount'] = $invoice->balance_due;
				break;				
			}
        
        return json_encode($data);
	}

}
