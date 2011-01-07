<?php
/**
* Rights authorization item controller class file.
*
* @author Christoffer Niska <cniska@live.com>
* @copyright Copyright &copy; 2010 Christoffer Niska
* @since 0.5
*/
class AuthItemController extends RController
{
	/**
	* @property RAuthorizer
	*/
	private $_authorizer;
	/**
	* @property CAuthItem the currently loaded data model instance.
	*/
	private $_model;

	/**
	* Initializes the controller.
	*/
	public function init()
	{
		$this->_authorizer = $this->module->getAuthorizer();
		$this->layout = $this->module->layout;
		$this->defaultAction = 'permissions';

		// Register the scripts
		$this->module->registerScripts();
	}

	/**
	* @return array action filters
	*/
	public function filters()
	{
		return array('accessControl');
	}

	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
	public function accessRules()
	{
		return array(
			array('allow', // Allow superusers to access Rights
				'actions'=>array(
					'permissions',
					'operations',
					'tasks',
					'roles',
					'generate',
					'create',
					'update',
					'delete',
					'removeChild',
					'assign',
					'revoke',
					'sortable',
				),
				'users'=>$this->_authorizer->getSuperusers(),
			),
			array('deny', // Deny all users
				'users'=>array('*'),
			),
		);
	}

	/**
	* Displays the permission overview.
	*/
	public function actionPermissions()
	{
		$dataProvider = new RPermissionDataProvider('permissions');

		// Get the roles from the data provider
		$roles = $dataProvider->getRoles();
		$roleColumnWidth = $roles!==array() ? 75/count($roles) : 0;

		// Initialize the columns
		$columns = array(
			array(
    			'name'=>'description',
	    		'header'=>Rights::t('core', 'Item'),
    			'htmlOptions'=>array(
    				'class'=>'permission-column',
    				'style'=>'width:25%',
	    		),
    		),
		);

		// Add a column for each role
    	foreach( $roles as $roleName=>$role )
    	{
    		$columns[] = array(
				'name'=>strtolower($roleName),
    			'header'=>$roleName,
    			'type'=>'raw',
    			'htmlOptions'=>array(
    				'class'=>'role-column',
    				'style'=>'width:'.$roleColumnWidth.'%',
    			),
    		);
		}

		$view = 'permissions';
		$params = array(
			'dataProvider'=>$dataProvider,
			'columns'=>$columns,
		);

		// Render the view
		isset($_POST['ajax'])===true ? $this->renderPartial($view, $params) : $this->render($view, $params);
	}

	/**
	* Displays the operation management page.
	*/
	public function actionOperations()
	{
		Yii::app()->user->returnUrl = array('authItem/operations');
		
		$dataProvider = new RAuthItemDataProvider('operations', array(
			'type'=>CAuthItem::TYPE_OPERATION,
			'sortable'=>array(
				'id'=>'RightsOperationTableSort',
				'element'=>'.operation-table',
				'url'=>$this->createUrl('authItem/sortable'),
			),
		));

		// Render the view
		$this->render('operations', array(
			'dataProvider'=>$dataProvider,
			'isBizRuleEnabled'=>$this->module->enableBizRule,
			'isBizRuleDataEnabled'=>$this->module->enableBizRuleData,
		));
	}

	/**
	* Displays the operation management page.
	*/
	public function actionTasks()
	{
		Yii::app()->user->returnUrl = array('authItem/tasks');
		
		$dataProvider = new RAuthItemDataProvider('tasks', array(
			'type'=>CAuthItem::TYPE_TASK,
			'sortable'=>array(
				'id'=>'RightsTaskTableSort',
				'element'=>'.task-table',
				'url'=>$this->createUrl('authItem/sortable'),
			),
		));

		// Render the view
		$this->render('tasks', array(
			'dataProvider'=>$dataProvider,
			'isBizRuleEnabled'=>$this->module->enableBizRule,
			'isBizRuleDataEnabled'=>$this->module->enableBizRuleData,
		));
	}

	/**
	* Displays the role management page.
	*/
	public function actionRoles()
	{
		Yii::app()->user->returnUrl = array('authItem/roles');
		
		$dataProvider = new RAuthItemDataProvider('roles', array(
			'type'=>CAuthItem::TYPE_ROLE,
			'sortable'=>array(
				'id'=>'RightsRoleTableSort',
				'element'=>'.role-table',
				'url'=>$this->createUrl('authItem/sortable'),
			),
		));

		// Render the view
		$this->render('roles', array(
			'dataProvider'=>$dataProvider,
			'isBizRuleEnabled'=>$this->module->enableBizRule,
			'isBizRuleDataEnabled'=>$this->module->enableBizRuleData,
		));
	}

	/**
	* Displays the generator page.
	*/
	public function actionGenerate()
	{
		// Get the generator and authorizer
		$generator = $this->module->getGenerator();

		// Createh the form model
		$model = new GenerateForm();

		// Form has been submitted
		if( isset($_POST['GenerateForm'])===true )
		{
			// Form is valid
			$model->attributes = $_POST['GenerateForm'];
			if( $model->validate()===true )
			{
				// Get the chosen items
				$items = array();
				foreach( $model->items as $itemname=>$value )
					if( (bool)$value===true )
						$items[] = $itemname;

				// Add the items to the generator as operations and run the generator
				$generator->addItems($items, CAuthItem::TYPE_OPERATION);
				if( ($generatedItems = $generator->run())!==false && $generatedItems!==array() )
				{
					Yii::app()->getUser()->setFlash($this->module->flashSuccessKey,
						Rights::t('core', 'Authorization items created.')
					);
					$this->redirect(array('authItem/permissions'));
				}
			}
		}

		// Get all items that are available to be generated
		$items = $generator->getControllerActions();

		// We need the existing operations for comparason
		$operations = $this->_authorizer->getAuthItems(CAuthItem::TYPE_OPERATION);
		$existingItems = array();
		foreach( $operations as $itemName=>$item )
			$existingItems[ $itemName ] = $itemName;

		Yii::app()->clientScript->registerScript('rightsGenerateItemTableSelectRows',
			"jQuery('.generate-item-table').rightsSelectRows();"
		);

		// Render the view
		$this->render('generate', array(
			'model'=>$model,
			'items'=>$items,
			'existingItems'=>$existingItems,
		));
	}

	/**
	* Creates an authorization item.
	*/
	public function actionCreate()
	{
		// Make sure that we have a type
		if( isset($_GET['type'])===true )
		{			
			// Create the authorization item form
			$formModel = new AuthItemForm('update');

			if( isset($_POST['AuthItemForm'])===true )
			{
				$formModel->attributes = $_POST['AuthItemForm'];
				if( $formModel->validate()===true )
				{
					// Create the item
					$item = $this->_authorizer->createAuthItem($formModel->name, $_GET['type'], $formModel->description, $formModel->bizRule, $formModel->data);
					$item = $this->_authorizer->attachAuthItemBehavior($item);

					// Set a flash message for creating the item
					Yii::app()->user->setFlash($this->module->flashSuccessKey,
						Rights::t('core', ':name created.', array(':name'=>$item->getNameText()))
					);

					// Redirect to the correct destination
					$this->redirect(Yii::app()->user->getReturnUrl(array('authItem/permissions')));
				}
			}
			
			// Render the view
			$this->render('create', array(
				'formModel'=>$formModel,
			));
		}
		else
		{
			throw new CHttpException(404, Rights::t('core', 'Invalid authorization item type.'));
		}
	}

	/**
	* Updates an authorization item.
	*/
	public function actionUpdate()
	{
		// Get the authorization item
		$model = $this->loadModel();
		
		// Create the authorization item form
		$formModel = new AuthItemForm('update');

		if( isset($_POST['AuthItemForm'])===true )
		{
			$formModel->attributes = $_POST['AuthItemForm'];
			if( $formModel->validate()===true )
			{
				// Update the item and load it
				$this->_authorizer->updateAuthItem($_GET['name'], $formModel->name, $formModel->description, $formModel->bizRule, $formModel->data);
				$item = $this->_authorizer->authManager->getAuthItem($formModel->name);
				$item = $this->_authorizer->attachAuthItemBehavior($item);

				// Set a flash message for updating the item
				Yii::app()->user->setFlash($this->module->flashSuccessKey,
					Rights::t('core', ':name updated.', array(':name'=>$item->getNameText()))
				);

				// Redirect to the correct destination
				$this->redirect(Yii::app()->user->getReturnUrl(array('authItem/permissions')));
			}
		}
		
		$type = Rights::getValidChildTypes($model->type);
		$exclude = array($this->module->superuserName);
		$childSelectOptions = Rights::getParentAuthItemSelectOptions($model, $type, $exclude);
		
		if( $childSelectOptions!==array() )
		{
			$childFormModel = new AuthChildForm();
		
			// Child form is submitted and data is valid
			if( isset($_POST['AuthChildForm'])===true )
			{
				$childFormModel->attributes = $_POST['AuthChildForm'];
				if( $childFormModel->validate()===true )
				{
					// Add the child and load it
					$this->_authorizer->authManager->addItemChild($_GET['name'], $childFormModel->itemname);
					$child = $this->_authorizer->authManager->getAuthItem($childFormModel->itemname);
					$child = $this->_authorizer->attachAuthItemBehavior($child);

					// Set a flash message for adding the child
					Yii::app()->user->setFlash($this->module->flashSuccessKey,
						Rights::t('core', 'Child :name added.', array(':name'=>$child->getNameText()))
					);

					// Reidrect to the same page
					$this->redirect(array('authItem/update', 'name'=>$_GET['name']));
				}
			}
		}
		else
		{
			$childFormModel = null;
		}

		// Set the values for the form fields
		$formModel->name = $model->name;
		$formModel->description = $model->description;
		$formModel->type = $model->type;
		$formModel->bizRule = $model->bizRule!=='NULL' ? $model->bizRule : '';
		$formModel->data = $model->data!==null ? serialize($model->data) : '';

		$parentDataProvider = new RAuthItemParentDataProvider($model);
		$childDataProvider = new RAuthItemChildDataProvider($model);

		// Render the view
		$this->render('update', array(
			'model'=>$model,
			'formModel'=>$formModel,
			'childFormModel'=>$childFormModel,
			'childSelectOptions'=>$childSelectOptions,
			'parentDataProvider'=>$parentDataProvider,
			'childDataProvider'=>$childDataProvider,
		));
	}

	/**
	 * Deletes an operation.
	 */
	public function actionDelete()
	{
		// We only allow deletion via POST request
		if( Yii::app()->request->isPostRequest===true )
		{
			// Load the item and save the name for later use
			$item = $this->_authorizer->authManager->getAuthItem($_GET['name']);
			$item = $this->_authorizer->attachAuthItemBehavior($item);
			$name = $item->getNameText();

			// Delete the item
			$this->_authorizer->authManager->removeAuthItem($_GET['name']);

			// Set a flash message for deleting the item
			Yii::app()->user->setFlash($this->module->flashSuccessKey,
				Rights::t('core', ':name deleted.', array(':name'=>$name))
			);

			// If AJAX request, we should not redirect the browser
			if( isset($_POST['ajax'])===false )
				$this->redirect(Yii::app()->user->getReturnUrl(array('authItem/permissions')));
		}
		else
		{
			throw new CHttpException(400, Rights::t('core', 'Invalid request. Please do not repeat this request again.'));
		}
	}

	/**
	* Removes a child from an authorization item.
	*/
	public function actionRemoveChild()
	{
		// We only allow deletion via POST request
		if( Yii::app()->request->isPostRequest===true )
		{
			// Remove the child and load it
			$this->_authorizer->authManager->removeItemChild($_GET['name'], $_GET['child']);
			$child = $this->_authorizer->authManager->getAuthItem($_GET['child']);
			$child = $this->_authorizer->attachAuthItemBehavior($child);

			// Set a flash message for removing the child
			Yii::app()->user->setFlash($this->module->flashSuccessKey,
				Rights::t('core', 'Child :name removed.', array(':name'=>$child->getNameText()))
			);

			// If AJAX request, we should not redirect the browser
			if( isset($_POST['ajax'])===false )
				$this->redirect(array('authItem/update', 'name'=>$_GET['name']));
		}
		else
		{
			throw new CHttpException(400, Rights::t('core', 'Invalid request. Please do not repeat this request again.'));
		}
	}

	/**
	* Adds a child to an authorization item.
	*/
	public function actionAssign()
	{
		// We only allow deletion via POST request
		if( Yii::app()->request->isPostRequest===true )
		{
			$model = $this->loadModel();

			if( isset($_GET['child'])===true && $model->hasChild($_GET['child'])===false )
				$model->addChild($_GET['child']);

			// if AJAX request, we should not redirect the browser
			if( isset($_POST['ajax'])===false )
				$this->redirect(array('authItem/permissions'));
		}
		else
		{
			throw new CHttpException(400, Rights::t('core', 'Invalid request. Please do not repeat this request again.'));
		}
	}

	/**
	* Removes a child from an authorization item.
	*/
	public function actionRevoke()
	{
		// We only allow deletion via POST request
		if( Yii::app()->request->isPostRequest===true )
		{
			$model = $this->loadModel();

			if( isset($_GET['child'])===true && $model->hasChild($_GET['child'])===true )
				$model->removeChild($_GET['child']);

			// if AJAX request, we should not redirect the browser
			if( isset($_POST['ajax'])===false )
				$this->redirect(array('authItem/permissions'));
		}
		else
		{
			throw new CHttpException(400, Rights::t('core', 'Invalid request. Please do not repeat this request again.'));
		}
	}

	/**
	* Processes the jui sortable.
	*/
	public function actionSortable()
	{
		// We only allow sorting via POST request
		if( Yii::app()->request->isPostRequest===true )
		{
			$this->_authorizer->authManager->updateItemWeight($_POST['result']);
		}
		else
		{
			throw new CHttpException(400, Rights::t('core', 'Invalid request. Please do not repeat this request again.'));
		}
	}

	/**
	* Returns the data model based on the primary key given in the GET variable.
	* If the data model is not found, an HTTP exception will be raised.
	*/
	public function loadModel()
	{
		if( $this->_model===null )
		{
			if( isset($_GET['name'])===true )
			{
				$this->_model = $this->_authorizer->authManager->getAuthItem($_GET['name']);
				$this->_model = $this->_authorizer->attachAuthItemBehavior($this->_model);
			}

			if( $this->_model===null )
				throw new CHttpException(404, Rights::t('core', 'The requested page does not exist.'));
		}

		return $this->_model;
	}
}
