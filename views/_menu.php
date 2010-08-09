<?php $this->widget('zii.widgets.CMenu', array(
	'items'=>array(
		array(
			'label'=>Yii::t('RightsModule.core', 'Permissions'),
			'url'=>array('default/permissions'),
			'itemOptions'=>array('class'=>'permissions'),
		),
		array(
			'label'=>Yii::t('RightsModule.core', 'Assignments'),
			'url'=>array('assignment/view'),
			'itemOptions'=>array('class'=>'assignments'),
		),
		array(
			'label'=>Yii::t('RightsModule.core', 'Operations'),
			'url'=>array('default/operations'),
			'itemOptions'=>array('class'=>'operations'),
		),
		array(
			'label'=>Yii::t('RightsModule.core', 'Tasks'),
			'url'=>array('default/tasks'),
			'itemOptions'=>array('class'=>'tasks'),
		),
		array(
			'label'=>Yii::t('RightsModule.core', 'Roles'),
			'url'=>array('default/roles'),
			'itemOptions'=>array('class'=>'roles'),
		),
		/*
		array(
			'label'=>Yii::t('RightsModule.core', 'Create Auth Item'),
			'url'=>array('authItem/create'),
			'itemOptions'=>array('class'=>'authItem'),
		),
		*/
		array(
			'label'=>Yii::t('RightsModule.core', 'Generator'),
			'url'=>array('authItem/generate'),
			'itemOptions'=>array('class'=>'generator'),
		),
	)
));	?>
