<h2 class="page-header">New <span class='text-muted'>Invoice</span>&nbsp;
<span><?= Html::anchor('accounts/purchases-invoice', '<i class="fa fa-level-down fa-fw fa-rotate-180"></i> Back to List', array('class' => 'btn btn-default btn-xs')); ?></span>
</h2>
<br>

<?= render('purchases/invoice/_form'); ?>
