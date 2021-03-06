<?= Form::open(array("class"=>"form-horizontal", "autocomplete" => "off")); ?>
<div class="row page-header">
    <div class="col-md-6">
        <!-- <h2>New <span class='text-muted'>Sale</span></h2> -->
        <?= Form::label('Find or Scan', 'item_search', array('class'=>'control-label')); ?>
        <?= Form::select('item_search', Input::post('item_search', isset($pos_invoice) ? $pos_invoice->item_search : ''), 
                        Model_Pos_Invoice_Item::listOptions(''), // enabled for Sales
                        array('class' => 'col-md-4 form-control select-from-list')); ?>      
    </div>
    <div class="col-md-2">
        <!-- <?php Form::label('Item Group', 'item_group', array('class'=>'control-label')); ?>
        <?php Form::select('item_group', Input::post('item_group', isset($pos_profile) ? $pos_profile->item_group : ''), 
                        Model_Pos_Invoice_Item::listOptions(), 
                        array('class' => 'col-md-4 form-control select-from-list')); ?> -->
        <!-- <div class="btn-group">
            <?php html_tag('button', array('class' => 'btn btn-default btn-sm'), 'List') ?>
            <?php html_tag('button', array('class' => 'btn btn-default btn-sm'), 'Images') ?>
        </div> -->
        <?= Html::anchor('pos/report/sales-register', 
                        '<i class="fa fa-line-chart fa-fw"></i>&ensp;Sales Register', 
                        array(
                            'class' => 'btn btn-default btn-sm btn-block', 
                            'style' => 'float:right; font-size: 110%'
                        )); ?>
    </div>
    <div class="col-md-4">
        <?= Form::label('Customer', 'customer_id', array('class'=>'control-label')); ?>
        <?= Form::select('customer_id', Input::post('customer_id', isset($pos_invoice) ? $pos_invoice->customer_id : ''), 
                        Model_Customer::listOptions(Model_Customer::CUSTOMER_TYPE_BUYER), 
                        array('class' => 'col-md-4 form-control select-from-list')); ?>
    </div>
</div>
<div class="row">
    <!-- Detail form -->
    <div class="col-md-8">
        <div id="bills" class="">
            <?php html_tag('label', array('class' => 'control-label'), ''); ?>
            <?= render('pos/invoice/item/index', array('pos_invoice_items' => isset($pos_invoice) ? $pos_invoice->items : array())); ?>
        </div>
    </div>
    <!-- Master form -->
    <div class="col-md-4">
        <?= Form::hidden('id', Input::post('id', isset($pos_invoice) ? $pos_invoice->id : Model_Pos_Invoice::getNextSerialNumber())); ?>
        <?= Form::hidden('customer_name', Input::post('customer_name', isset($pos_invoice) ? $pos_invoice->customer_name : '')); ?>
        <?= Form::hidden('status', Input::post('status', isset($pos_invoice) ? $pos_invoice->status : Model_Pos_Invoice::INVOICE_STATUS_OPEN)); ?>
        <?= Form::hidden('paid_status', Input::post('paid_status', isset($pos_invoice) ? $pos_invoice->paid_status : '')); ?>
        <?= Form::hidden('issue_date', Input::post('issue_date', isset($pos_invoice) ? $pos_invoice->issue_date : date('Y-m-d'))); ?>
        <?= Form::hidden('due_date', Input::post('due_date', isset($pos_invoice) ? $pos_invoice->due_date :  date('Y-m-d'))); ?>
        <?= Form::hidden('fdesk_user', Input::post('fdesk_user', isset($pos_invoice) ? $pos_invoice->fdesk_user : $uid)); ?>
        <?php Form::hidden('source_id', Input::post('source_id', isset($pos_invoice) ? $pos_invoice->source_id : '')); ?>            

        <!-- <div class="form-group">
            <div class="col-md-12">
                <div class="btn-group">
                <?= html_tag('button', array('class' => 'btn btn-default'), 'Park') ?>
                <?= html_tag('button', array('class' => 'btn btn-default'), 'Cancel') ?>
                <?= html_tag('button', array('class' => 'btn btn-default'), 'Lock') ?>
                </div>
            </div>
        </div> -->

        <?= render('pos/invoice/saletotal', array()); ?>

        <div class="form-group">
            <div class="col-md-12">
                <!-- trigger suspend via F9 -->
                <?php html_tag('button', array('class' => 'btn btn-success', 'data-bind' => 'click: save'), 'Save') ?>
                <!-- trigger submit via F10 -->
                <!-- Pay Now assumes Cash Sale Customer -->
                <!-- Show no. of items in button float:left i.e. in place of icon -->
                <?= Form::submit('submit', 'Pay Now', array('class' => 'btn btn-primary btn-block', 'style' => 'font-size: 125%; font-weight: bold')); ?>
                <!-- or -->
                <!-- Pay Later requires actual Customer (not Cash Sale) -->
                <!-- trigger submit without payment via F8 -->
                <?php Html::anchor('pos/payment/later', 'Pay Later', array('class' => 'btn btn-info btn-block', 'style' => 'font-size: 125%; font-weight: bold')); ?>
            </div>
        </div>
        <div class="form-group">
            <div class="col-md-12">
                <?= Form::label('Notes', 'notes', array('class'=>'control-label')); ?>
                <?= Form::textarea('notes', Input::post('notes', isset($pos_invoice) ? $pos_invoice->notes : ''), 
                                    array('class' => 'col-md-4 form-control', 'rows' => 4)); ?>
            </div>
        </div>
    </div>
</div>

<?= Form::close(); ?>

<script>
    // Fetch dependent drop down list options
    $('#form_source').on('change', function() { 
        $.ajax({
            type: 'post',
            url: '/accounts/sales-invoice/get-source-list-options',
            // dataType: 'json',
            data: {
                // console.log($(this).val());
                'source': $(this).val(),
            },
            success: function(listOptions) 
            {
                var selectOptions = '<option value="" selected></option>';
                $.each(JSON.parse(listOptions), function(index, listOption)               
                {
                    selectOptions += '<option value="' + index + '">' + listOption + '</option>';
                });
                $('#form_source_id').html(selectOptions);
            },
            error: function(jqXhr, textStatus, errorThrown) {
                console.log(errorThrown)
            }
        });
    });

    $('#form_source_id').on('change', function() { 
        $.ajax({
            type: 'post',
            url: '/accounts/sales-invoice/get-source-info',
            // dataType: 'json',
            data: {
                // console.log($(this).val());
                'source': $('#form_source').val(),
                'source_id': $(this).val(),
            },
            success: function(data) 
            {
                // console.log(data);
                data = JSON.parse(data);
                $('#form_customer_name').val(data.customer_name);
                $('#form_billing_address').val(data.email_address);
            },
            error: function(jqXhr, textStatus, errorThrown) {
                console.log(errorThrown)
            }
        });
    });

</script>