<?php
use Orm\Model;

class Model_Product_Group extends Model
{
    // System-defined (must NOT be deleted) but can be disabled
    // 

	protected static $_properties = array(
		'id',
		'name',
		'code',
        'enabled',
        'is_default',
        'parent_id',
        'default_supplier',
        'fdesk_user',
		'created_at',
		'updated_at',
	);

    protected static $_belongs_to = array(
		'parent' => array(
			'key_from' => 'parent_id',
			'model_to' => 'Model_Pos_Group',
			'key_to' => 'id',
			'cascade_save' => false,
			'cascade_delete' => false,
        ),
    );

	protected static $_observers = array(
		'Orm\Observer_CreatedAt' => array(
			'events' => array('before_insert'),
			'mysql_timestamp' => false,
		),
		'Orm\Observer_UpdatedAt' => array(
			'events' => array('before_save'),
			'mysql_timestamp' => false,
		),
	);

	public static function validate($factory)
	{
		$val = Validation::forge($factory);
		$val->add_field('name', 'Name', 'required|max_length[255]');
		$val->add_field('code', 'Code', 'required|max_length[255]');
		$val->add_field('enabled', 'Enabled', 'required');
		// $val->add_field('parent_id', 'Parent', 'valid_string[numeric]');

		return $val;
	}

    public static function listOptions()
    {
		$items = DB::select('st.id', 'st.name')
                    ->from(array('product_group', 'st'))
                    ->join(array('product_group', 'stp'), 'INNER')->on('stp.id', '=', 'st.parent_id')
                    ->where([
                        'st.enabled' => true,
                        ['st.parent_id', '!=', 0]
                    ])
                    ->order_by('stp.name', 'ASC')
                    ->order_by('st.name', 'ASC')
                    ->execute()
                    ->as_array();
        
		$list_options = array('' => '');

		foreach($items as $item) {
			$list_options[$item['id']] = $item['name'];
        }
        
		return $list_options;
    }

    public static function listOptionsParentGroup()
    {
		$items = DB::select('id','name')
						->from('product_group')
						->where([
                            'enabled' => true,
                            ['parent_id', 'in', [null, 0]]
                        ])
						->execute()
						->as_array();
        
		$list_options = array('' => '-- Select parent group --');

		foreach($items as $item) {
			$list_options[$item['id']] = $item['name'];
        }
        
		return $list_options;
    }
}
