<?php
/**
 * M Active Record Relations Behavior
 * Поведение для организации работы с Relations
 * 
 * @author Jasur Mirkhamidov <mirkhamidov.jasur@gmail.com>
 * 
 * Производитсья проверка наличия всех необходимых полей и значений в inherited модели!
 * 
 * inherited модель - Модель данных, в которой хранятся данные относящиейся к owner модели
 * owner модель     - Модель данных, к которой привязывается данное поведение, и для которой
 * 						необходимо выводить данные из inherited модели
 * 
 * 
 * У родительской (owner) модели:
 * 	управляемые параметры (при объявлении поведения у owner модели):
 * 		inheritedClass		- REQUIRED STRING !!! Название класса модели, которая 
 * 							участвует в роли inherited модели, в которой содержаться данные
 * 							относящиеся к owner модели
 * 							Так же см. {@link inheritedClass}
 * 		showInAttribute		- REQUIRED STRING! Название поля для owner модели, в котором будут содержаться
 * 							данные из inherited модели относящиейся к owner модели.
 * 							Так же см. {@link _showInAttribute}
 * 		ownerSetModel		- STRING, Default=null. Необходимо указывать, если для связи owner модели  
 * 							inherited моделью в поле {@link attrModel} необходимо вставить значение другой модели.
 * 							Например: 
 * 									owner модель =  Model1 с базовой моделью MainModel
 * 									inherited модель = IModel
 * 								При записи в inherited модель связь необходимо установить не через модель 
 * 									Model1, а через ее базовую модель MainModel
 * 		attrModel			- STRING, Default="relation". Название поля inherited модели, 
 * 							куда будет вставлено название owner модели.
 * 							Так же см. {@link attrModel}
 * 		attrModelPk			- STRING, Default="relation_id". Название поля в inherited модели, 
 * 							куда будет вставлено значение PK onwer модели.
 * 							Так же см. {@link attrModelPk}
 * 		attrModelAttr		- STRING, Default="relation_attr". Название поля в inherited модели, 
 * 							куда будет вставлено название атрибута owner модели, в котором 
 * 							будут содержаться данные принадлежащие owner модели из inherited модели.
 * 							Будет содержать значение {@link showInAttribute}
 * 							Так же см. {@link attrModelAttr}
 * 		showInAttributeLabel- STRING, Default="showInAttribute с большой буквы и _->' '". 
 * 							используется как label, для fieldsConfig 
 * 							будут содержаться данные принадлежащие owner модели из inherited модели.
 * 							Так же см. {@link _showInAttributeLabel}; {@link getShowInAttributeLabel}; {@link setShowInAttributeLabel}
 * 		attrModelTable	- STRING, Default="relation_table". Название поля inherited модели,
 * 							куда будет вставлено название таблицы owner модели
 * 							для выполнения прямых запросов на уровне БД или др.целей
 * 							Так же см. {@link attrModelTable}
 * 		requiredToFill	- BOOL, Default="false".
 * 							Установка на объязательность заполнения указанного блока поведения
 * 		joinType 		- CONST STRING, Default=MARRelationsBehavior::JOINMULTI.
 * 							Указатель, как использовать привязку.
 * 							Если:
 * 								MARRelationsBehavior::JOINMULTI 	- То в указанном showInAttribute будет массив из inherited моделей
 * 								MARRelationsBehavior::JOINSINGLE 	- То в указанном showInAttribute будет массив одна запись inherited модели
 * 							Так же см. {@link joinType}
 * 
 * У наследуемой (inherited) модели:
 * 	Вносятся изменения (автоматически, самим поведением):
 * 		Owner объекта:
 * 			rules:
 * 				{@link attach} Удаляются проверка атрибутов в правилах указанных в {@link _removeFromValidators}
 * 				{@link attach} Добавляются правила типа unsafe для атрибутов 
 * 				{@link afterValidate} Добавляются правила типа MModelRelationsValidator
 * 
 * 		Inherited объекта:
 * 
 * 
 * HowTo:
 * 		Как подключить к owner(основной) модели:
 * 			public function behaviors()
 * 			{
 * 				return [
 * 					'{BEHAVIOR_UNIQ_NAME}' => [
 * 						'class'           => 'MARRelationsBehavior',
 * 						'inheritedClass'  => '{INHERITED_MODEL_CLASS}',
 * 						'showInAttribute' => '{SHOW_IN_THIS_ATTRIBUTE}',
 * 					],
 * 					...
 * 					// Таких блоков может быть несколько, для разных showInAttribute
 * 				];
 * 			}
 * 	
 * 			Где:
 * 				{BEHAVIOR_UNIQ_NAME}     - Уникальное название поведения (требуется Yii)
 * 				{INHERITED_MODEL_CLASS}  - Привязываемый класс-модель (куда/откуда пишется/берется дополниельная запись основной модели)
 * 				{SHOW_IN_THIS_ATTRIBUTE} - Название атрибута основной модели для показа записей привязываемого класса-модели
 * 
 * 		Как получить из inherited модели owner запись через стандартный relations():
 * 			в метода relations() inherited модели необходимо добавить запись:
 * 				array('{OWNERS_UNIQ_ALIAS}' => array(self::BELONGS_TO, '{OWNERS_MODEL_CLASS}', array('relation_id'=>'id'), 'alias'=>'{OWNERS_UNIQ_ALIAS}'))
 * 			где:
 * 				{OWNERS_UNIQ_ALIAS}  - Алиас названия owner модели в запросе/атрибуте inherited модели (используется в SQL запросе)
 * 				{OWNERS_MODEL_CLASS} - Название класса owner модели
 * 		
 * 			Пример использования:
 * 				Объявление в inherited модели:
 * 					public function relations()
 * 					{
 * 						$_r = parent::relations();
 * 						$_r['{OWNERS_UNIQ_ALIAS}'] = array(self::BELONGS_TO, '{OWNERS_MODEL_CLASS}', array('relation_id'=>'id'), 'alias'=>'{OWNERS_UNIQ_ALIAS}');
 * 						return $_r;
 * 					}
 * 				Вызов связанной owner модели через inherited модель:
 * 					${INHERITED}->{OWNERS_UNIQ_ALIAS}
 * 		
 * 		Удаление связанных inherited моделей
 * 			* При удалении owner модели, все связанные inherited модели удаляются автоматически
 * 			* Если необходимо удалить отдельную inherited запись, то удалять через свой контроллер inherited модели
 * 			* Так же, можно передать isdeleted=1 для inherited записи
 * 
 */
class MARRelationsBehavior extends CActiveRecordBehavior
{
	/**
	 * Используется как значение fieldType в методе setFieldsConfig owner модели
	 *  при добавлении поля showInAttribute 
	 * Так же используется в: {@link MActiveRecord}; {@link }
	 */
	const FIELDS_CONFIG_KEY = 'mrelation';

	/**
	 * @var string Для уставновки связи owner модели  с inherited моделью, 
	 * 	если Необходимо подставить значние другого класса.
	 * Внимание! Влияет только на запись в inherited модель, 
	 * 	другие связи останутся через реально связанную модель. {@link getOwnerClassName}
	 * @access private
	 * Геттер {@link getOwnerSetModel} или {@link ownerSetModel}
	 * Сеттер {@link setOwnerSetModel} или {@link ownerSetModel}
	 */
	private $_ownerSetModel = null;

	/**
	 * @var string Название inherited модели
	 * @access public
	 */
	public $inheritedClass;

	/**
	 * Устанавливается название атрибута для owner модели,
	 * в котором должны выводиться значения inherited модели
	 * @var string Название виртуального поля для owner
	 * @access private
	 */
	public $showInAttribute;

	/**
	 * Геттер {@link getShowInAttributeLabel}
	 * Сеттер {@link setShowInAttributeLabel}
	 * @var Array Для хранения лэйбла для каждого showInAttribute поля owner модели
	 * @access private
	 */
	private $_showInAttributeLabel;

	/**
	 * Required
	 * Пишется название модели
	 * @var string Название поля
	 * @access public
	 */
	public $attrModel		= 'relation';

	/**
	 * Required
	 * Пишется идентификатор модели
	 * @var string Название поля
	 * @access public
	 */
	public $attrModelPk		= 'relation_id';

	/**
	 * @var string Название поля, в котором в owner модели отобразить inherited модель
	 * @access public
	 */
	public $attrModelAttr	= 'relation_attr';

	/**
	 * Пишется название таблицы. 
	 * Уровень БД. Используется не всегда!
	 * @var string Название поля
	 * @access public
	 */
	public $attrModelTable	= 'relation_table';

	/**
	 * @var Bool Установка атрибута на объязательность заполнения
	 * @access public
	 */
	public $requiredToFill = false;

	/**
	 * Кэш. Данные получаются через {@link getOwnerClassName}
	 * @var string Название родительской модели 
	 * @access private
	 */
	private $_ownerClassName=null;

	/**
	 * @todo TODO: Добавлять правила родительской (owner) модели
	 * @var Связь между onwer и inherited моделями!
	 */
	const JOINSINGLE	= 'single';
	const JOINMULTI		= 'multiple';

	/**
	 * Установика типа связи между owner и inherited моделями
	 * По умолчанию multiple
	 * Управляется через owner модель
	 * Устанавлвиает в filedsConfig значение joinType
	 * @access public
	 */
	public $joinType = self::JOINMULTI;

	/**
	 * Сеттер {@link setRemoveFromValidators} 
	 * Геттер {@link getRemoveFromValidators}
	 * @var Array Валидаторы, из которых необходимо удалить атрибуты
	 * @access private
	 */
	private $_removeFromValidators = array(
		'CRequiredValidator'	=> '' , 
		// 'CSafeValidator'		=> '' ,
		// 'CStringValidator'	=> '' ,
	);

	/**
	 * Инициализация {@link startTransaction}
	 * Управление {@link doTransaction}
	 * @var Если ранее не запущена транзакция то объект транзакции. 
	 * @static
	 * @access private
	 */
	private static $_transaction=null;

	/**
	 * Геттер {@link getInheritedModels}
	 * Сеттер {@link setInheritedModels}
	 * @var Кэш для записей inherited модели
	 */
	private $_inheritedModels=array();

	/**
	 * Для корректного завершения транзакций
	 * Устанавливается в {@link checkAttributesExists}
	 * Работает в {@link afterSave}
	 * @var Array Для службеного пользования
	 * @access private
	 * @static
	 */
	private static $_toFinishTransaction = null;

	/**
	 * Используется для хранения всех объявленных значений
	 * 	в owner модели для данного поведения. Данные в виде:
	 * 		[
	 * 			{значение showInAttribute} => 
	 * 			[
	 * 				все значения который указаны для конкретного showInAttribute
	 * 				в owner модели в разделе поведений
	 * 			],
	 * 			...
	 * 		]
	 * 	значения 
	 * @var Инициализируется в методе {@link checkAttributesExists}
	 * @static
	 * @access private
	 */
	private static $_hasBehaviorAttributes = false;

	/**
	 * TODO: Нуждается в более подробном изучении использования и рефакторинге
	 * Used in: {@link setInheritedModels}
	 * Seted in: {@link getInheritedModels}
	 * @var array Загруженные идентификаторы через {@link getInheritedModels}
	 * @access private
	 */
	private $loadedids = array();

	/**
	 * Главный сеттер
	 * @access public
	 * @param string $name имя свойства
	 * @param mixed $value значение свойства
	 * @return mixed
	 */
	public function __set($name,$value)
	{
		if($name==$this->showInAttribute)
		{
			if($this->joinType==self::JOINSINGLE)
			{
				if(!empty($value) && !Y::isArrayAssoc($value))
				{
					$_m=null;
					if(YII_DEBUG) $_m = ' Массив данных должен быть ассоциативным, так как joinType=single.';
					throw new CException('К аттрибуту "'.$this->showInAttribute.'" указана неправильная струкрута данных.'.$_m);
				}
				$this->setInheritedModels($value);
			}
			else
			{
				if(!empty($value) && Y::isArrayAssoc($value))
				{
					$_m=null;
					if(YII_DEBUG) $_m = ' Массив данных должен быть индексированным и содержать ассоциативный, так как joinType=multiple.';
					throw new CException('К аттрибуту "'.$this->showInAttribute.'" указана неправильная струкрута данных.'.$_m);
				}
				for($i=0, $max=count($value); $i<$max; ++$i)
				{
					$this->setInheritedModels($value[$i]);
				}
				
			}
			return $this->_inheritedModels;
		}
		return parent::__set($name,$value);
	}

	/**
	 * Главный геттер
	 * @access public
	 * @param string $name the property name or event name
	 * @return mixed the property value, event handlers attached to the event, or the named behavior
	 */
	public function __get($name)
	{
		if($name==$this->showInAttribute)
		{
			return $this->getInheritedModels();
		}
		return parent::__get($name);
	}

	/**
	 * Определяет, может ли быть установлено свойство.
	 * Используется родительским классом
	 * @param string $name имя свойства
	 * @return boolean
	 */
	public function canSetProperty($name)
	{
		if($this->canGetSetProperty($name)) return true;
		return parent::canSetProperty($name);
	}

	/**
	 * Определяет, можно ли читать свойство.
	 * Используется родительским классом
	 * @access public
	 * @param string $name имя свойства
	 * @return boolean
	 */
	public function canGetProperty($name)
	{
		if($this->canGetSetProperty($name)) return true;
		return parent::canSetProperty($name);
	}

	/**
	 * Проверка для {@link canGetProperty} и {@link canSetProperty}
	 * @access private
	 * @param string $name имя свойства
	 * @return bool
	 */
	private function canGetSetProperty($name)
	{
		if($name==$this->showInAttribute) return true;
		return false;
	}

	public function attach($owner)
	{
		parent::attach($owner);
		// HOTFIX: from crashes in console migrations!
		if(Y::currAppClass()=='CConsoleApplication' && !Y::isTableExists($this->getInheritedClassName()))
		{
			$this->setEnabled(false);
		}
	}

	/**
	 * Действия до validate owner модели
	 * Внимание! Вызывается два раза, при save и validate
	 * @access public
	 * @param CEvent $event
	 * @return bool
	 */
	public function beforeValidate($event)
	{
		// INFO: Проверка на наличие необходимых атрибутов как для конфигурации поведения так и модели!
		// 			При ошибке Exception!
		$this->checkAttributesExists();
		
		// INFO: Проверка на необходимость заполнения указанного поля
		if($this->requiredToFill===true && !$this->hasInheritedModels())
		{
			$this->getOwner()->addError($this->showInAttribute, 'Связанное поле "'.$this->showInAttribute.'" не может быть пустым.');
			$event->isValid=false;
			return false;
		} 
		else
		{
			if($this->joinType==self::JOINSINGLE)
			{
				// INFO: установка доп.атрибутов для inherited модели
				$this->_setRelationsToInherited($this->getInheritedModels());
				// INFO: validate для inherited модели
				$this->validateEachInheritedModel($this->_inheritedModels,0);
			}
			elseif($this->joinType==self::JOINMULTI)
			{
				// INFO: см.выше, только для multiple inherited модели
				foreach($this->_inheritedModels as $index => $inheritedValue)
				{
					$this->_setRelationsToInherited($this->_inheritedModels[$index]);
					$this->validateEachInheritedModel($this->_inheritedModels[$index], $index);
				}
			}
			else
			{
				throw new CException('Указана некорректная свзяь для поведения.');
			}

			// INFO: Если в ходе валидации были ошибки, то они отдаются в owner модель, соответствнно вывод
			if($this->getOwner()->hasErrors())
			{
				$event->isValid = false;
				return false;
			}
		}
	}

	/**
	 * Действия до сохранения owner модели
	 * Старт транзакции 
	 * Заполнение for_transaction_end:all
	 * @access public
	 * @param CEvent $event
	 */
	public function beforeSave($event)
	{
		$this->startTransaction();
		// INFO: Проверка на наличие необходимых атрибутов как для конфигурации поведения так и модели!
		// 			При ошибке Exception!
		if(empty(self::$_toFinishTransaction))
		{
			self::$_toFinishTransaction = [
				'for_transaction_end' => [
					'all'		=> [],
					'success'	=> [],
					'fail'		=> [],
				]
			];
		}

		$this->generateTransForAll('--');
	}

	/**
	 * Действия после сохранения owner модели
	 * Также происходит окончание транзакций
	 * @access public
	 * @param CEvent $event
	 */
	public function afterSave($event)
	{
		if($this->joinType==self::JOINSINGLE)
		{
			// INFO: сохранение отдельной записи
			$this->saveEachInheritedModel($this->getInheritedModels(),0);
		}
		elseif($this->joinType==self::JOINMULTI)
		{
			foreach($this->getInheritedModels() as $index => $inheritedValue)
			{
				$this->saveEachInheritedModel($this->_inheritedModels[$index], $index);
			}
		}
		else
		{
			throw new CException('Неправильно указан способ привязки поведения.');
		}

		// INFO: устанавливает конец транзакции
		$this->canEndTransaction();
	}

	/**
	 * Действие, до удаления owner модели
	 * Старт транзакции 
	 * Заполнение for_transaction_end:all
	 * @access public
	 * @param CEvent $event
	 */
	public function beforeDelete($event)
	{
		$this->startTransaction();
		// INFO: Проверка на наличие необходимых атрибутов как для конфигурации поведения так и модели!
		// 			При ошибке Exception!
		if(empty(self::$_toFinishTransaction))
		{
			self::$_toFinishTransaction = [
				'for_transaction_end' => [
					'all'		=> [],
					'success'	=> [],
					'fail'		=> [],
				],
			];
		}
		$this->generateTransForAll('--d--');
	}

	/**
	 * Действия, после удаления записи owner модели
	 * Также происходит окончание транзакций
	 * Перегрузка
	 * @access public
	 * @param CEvent $event
	 */
	public function afterDelete($event)
	{
		if(!empty($this->_inheritedModels))
		{
			if($this->joinType==self::JOINSINGLE)
			{
				$this->deleteEachInheritedModel($this->_inheritedModels, 0);
			}
			else
			{
				for($i=0, $max=count($this->getInheritedModels()); $i<$max; ++$i)
				{
					$this->deleteEachInheritedModel($this->_inheritedModels[$i], $i);
				}
			}
		}
		$this->canEndTransaction();
	}

	

	/**
	 * Проверка каждой записи для inherited модели
	 * Если возникают ошибки, записывается в owner модель
	 * @access private
	 * @param Object 	$model Передаваемая для проверки inherited модель
	 * @param int 		$index Индекс записи у owner модели!
	 * @return boolean
	 */
	private function validateEachInheritedModel($model, $index)
	{
		// INFO: lets validate
		if($model->validate())
		{
			return true;
		}
		else
		{
			$this->getOwner()->addError($this->showInAttribute, $model->getErrors());
			return false;
		}
	}

	/**
	 * Сохранение каждой inherited записи в БД
	 * Если возникают ошибки то по идее никуда не записываются, 
	 * 	так как до этого происходит beforeValidate проверка!
	 * @access private
	 * @param Object 	$model Передаваемая для сохранения inherited модель
	 * @param int 		$index Индекс записи у owner модели!
	 */
	private function saveEachInheritedModel($model, $index)
	{
		// INFO: Запишем PrimaryKey значение owner модели в inherited модель
		if($this->getOwner()->getPrimaryKey())
		{
			$model->{$this->attrModelPk} = $this->getOwner()->getPrimaryKey();
		}

		if(!$model->save())
		{
			self::$_toFinishTransaction['for_transaction_end']['fail'][] = $this->_createIndexForInherited($model, $index);
		}
		else
		{
			self::$_toFinishTransaction['for_transaction_end']['success'][] = $this->_createIndexForInherited($model, $index);
		}
	}

	/**
	 * Удаление каждоый inherited модели
	 * @access private
	 * @param Object 	$model Передаваемая для сохранения inherited модель
	 * @param int 		$index Индекс записи у owner модели!
	 */
	private function deleteEachInheritedModel(&$model, $index)
	{
		if($model->getIsDeleted()) return true;
		if(!$model->delete())
		{
			self::$_toFinishTransaction['for_transaction_end']['fail'][] = $this->_createIndexForInherited($model, $index).'='.$model->isdeleted;
		}
		else
		{
			self::$_toFinishTransaction['for_transaction_end']['success'][] = $this->_createIndexForInherited($model, $index).'='.$model->isdeleted;
		}
	}

	/**
	 * Создание унифицированного индекса для inherited моделей
	 * Используется как метка
	 * Необходим для методов *EachInheritedModel
	 * @access private
	 * @param Object 	$model Передаваемая для сохранения inherited модель
	 * @param int 		$index Индекс записи у owner модели!
	 */
	private function _createIndexForInherited($model, $index)
	{
		// $_d = debug_backtrace(false, 4);
		return implode('-', [ 
			$model->id 
			, $this->getOwnerClassName()
			, get_class($model)
			, $this->showInAttribute
			, $index
			// , $model->id 
			// ,'|->'.$_d[1]['function']
			// ,$_d[2]['function']
			// ,$_d[3]['function']
			] );
	}

	/**
	 * Устанавливает в inherited модель необходимые данные
	 * Используется в {@link beforeSave}
	 * @access private
	 * @param Object $inheritedModel для данной модели устанавливаются данные
	 */
	private function _setRelationsToInherited($inheritedModel)
	{
		$inheritedModel->{$this->attrModel}		= $this->getOwnerSetModel();
		$inheritedModel->{$this->attrModelAttr}	= $this->showInAttribute;
		if($inheritedModel->hasAttribute($this->attrModelTable)) 
		{
			$inheritedModel->{$this->attrModelTable} = Y::trimTableName($this->getOwner()->tableName());
		}
	}

	/**
	 * Установка атрибутов для owner модели
	 * Обработка в {@link MActiveRecord}
	 * Внимание!
	 * @access public
	 * @return array
	 */
	public function setOwnerAttributes()
	{
		return array(
			$this->showInAttribute => $this->getOwner()->{$this->showInAttribute},
		);
	}

	/**
	 * Получить запиcи inherited модели
	 * Так же подгружает и сохраненные модели из БД
	 * @access private
	 * @return array
	 */
	public function getInheritedModels()
	{
		// Подгрузим уже загруженные данные
		if($this->_inheritedModels==array() && !$this->getOwner()->getIsNewRecord())
		{
			$_criteria = new CDbCriteria;
			$_criteria->select = '*';
			$_criteria->addCondition($this->attrModel.'=:ownerModelName AND '.$this->attrModelPk.'=:ownerModelPk AND '.$this->attrModelAttr.'=:ownerModelAttr');
			$_criteria->params = array(
				':ownerModelName'	=>$this->getOwnerSetModel(),
				':ownerModelPk'		=>$this->getOwnerPk(),
				':ownerModelAttr'	=>$this->showInAttribute,
			);

			if($this->joinType==self::JOINMULTI)
			{
				$_data = MActiveRecord::model($this->getInheritedClassName())->findAll($_criteria);
				if(!empty($_data))
				{
					for($i=0, $max=count($_data); $i<$max; ++$i)
					{
						$this->loadedids[] = $_data[$i]->{$_data[$i]->getTableSchema()->primaryKey};
					}
					$this->_inheritedModels = $_data;
				}
			}
			else
			{
				$_data = MActiveRecord::model($this->getInheritedClassName())->find($_criteria);
				if(!empty($_data))
				{
					$this->loadedids = $_data->{$_data->getTableSchema()->primaryKey};
					$this->_inheritedModels = $_data;
				}
			}
		}
		return $this->_inheritedModels;
	}

	

	/**
	 * Запись inherited моделей
	 * @access private
	 * @param array $v Значения для модели
	 * @return array
	 */
	private function setInheritedModels($v)
	{
		if(empty($v)) return false;

		$_isAlreadyLoaded = false;
		$this->generateTransForAll('--V--');

		// TODO: Проверка на существование класса, иначе эксепшн
		$_inheritedModel = new $this->inheritedClass;

		// Если указан PK, то необходимо инициализировать его!
		if(isset($v[$_inheritedModel->getTableSchema()->primaryKey]))
		{
			if($this->joinType==self::JOINMULTI && !empty($this->loadedids) && in_array($v[$_inheritedModel->getTableSchema()->primaryKey], $this->loadedids))
			{
				for($i=0, $max=count($this->_inheritedModels); $i<$max; ++$i)
				{
					if($this->_inheritedModels[$i]->{$this->_inheritedModels[$i]->getTableSchema()->primaryKey} == $v[$_inheritedModel->getTableSchema()->primaryKey]) 
					{
						$_isAlreadyLoaded = true;
						if(isset($v['isdeleted']) && $v['isdeleted']>0)
						{
							
							$this->deleteEachInheritedModel($this->_inheritedModels[$i],$i);
							unset($this->_inheritedModels[$i]);
							$this->_inheritedModels = array_values($this->_inheritedModels);
							return $this->_inheritedModels;
						}
						else
						{
							$_inheritedModel = &$this->_inheritedModels[$i];
						}
						
						break;
					}
				}
			}
			elseif($this->joinType==self::JOINSINGLE && !empty($this->loadedids) && $this->loadedids==$v[$_inheritedModel->getTableSchema()->primaryKey] )
			{
				$_isAlreadyLoaded = true;
				$_inheritedModel = &$this->_inheritedModels;
			}
			else
			{
                $_tmpClassName = get_class($_inheritedModel);
                $_tmpId = $v[$_inheritedModel->getTableSchema()->primaryKey];
				$_inheritedModel = $_inheritedModel::model()->findByPk($v[$_inheritedModel->getTableSchema()->primaryKey]);
                if(empty($_inheritedModels))
                {
                    $_m=null;
                    if(YII_DEBUG)
                    {
                        $_m = ' ('.__METHOD__.')';
                    }
                    throw new CException("Ошибка при получении данных из \"$_tmpClassName\" с идентификатором  $_tmpId.$_m\n");
                }
			}
			// TODO: Проверка, иначе экспепшн
			if($_inheritedModel)unset($v[$_inheritedModel->getTableSchema()->primaryKey]);
		}

		$_inheritedModel->setAttributes($v);

		// INFO: Удаление не нужных проверок атрибутов
		$_toAttribute = array($this->attrModel, $this->attrModelPk);
		if($_inheritedModel->hasAttribute($this->attrModelAttr)) $_toAttribute[] = $this->attrModelAttr;
		if($_inheritedModel->hasAttribute($this->attrModelTable)) $_toAttribute[] = $this->attrModelTable;
		$_inheritedModel->deleteValidatorOnFly($this->removeFromValidators, $_toAttribute);
		// INFO: set unsafe Rules!
		$validator = CValidator::createValidator('unsafe', $_inheritedModel, $_toAttribute );
		$_inheritedModel->validatorList->add($validator);

		if($_isAlreadyLoaded===false)
		{
			if($this->joinType==self::JOINMULTI)
			{
				$this->_inheritedModels[] = $_inheritedModel;
			}
			else
			{
				$this->_inheritedModels = $_inheritedModel;
			}
		}
		return $this->_inheritedModels;
	}

	/**
	 * Проверка на существование inherited модели
	 * Относительно joinType
	 * @access private
	 * @return bool
	 */
	private function hasInheritedModels()
	{
		if($this->joinType==self::JOINMULTI)
		{
			return (bool)count($this->getInheritedModels());
		}
		else
		{
			return !empty($this->getInheritedModels());
		}
	}

	/**
	 * Геттер. {@link _ownerClassName}
	 * Получить название owner модели
	 * @access public
	 * @return string
	 */
	public function getOwnerClassName()
	{
		if($this->_ownerClassName===null) {
			if(__CLASS__ != get_class($this->getOwner())) 
			{
				$this->_ownerClassName = get_class($this->getOwner());
			}
		}
		return $this->_ownerClassName;
	}

	/**
	 * Геттер.
	 * Получить название класса inherited модели
	 * @access public
	 * @return string
	 */
	public function getInheritedClassName()
	{
		return $this->inheritedClass;
	}

	/**
	 * Геттер для {@link _removeFromValidators}
	 * @access protected
	 * @return Array
	 */
	protected function getRemoveFromValidators()
	{
		return $this->_removeFromValidators;
	}

	/**
	 * Сеттер для {@link _removeFromValidators}
	 * @access protected
	 */
	protected function setRemoveFromValidators($v)
	{
		$this->_removeFromValidators[$v]='';
	}

	/**
	 * Установка fieldsConfig из inherited модели для owner модели
	 * Вызывается из {@link MActiveRecord::getFieldsConfig_filterByViewType}
	 * @access public
	 * @param Array $attrConfig Передается значение указанное в методе setFieldsConfig owner модели
	 * @param bool $_only_struct Передается из метода {@link MActiveRecord::getFieldsConfig_filterByViewType}
	 * @param int $viewType Передается из метода {@link MActiveRecord::getFieldsConfig_filterByViewType}
	 * @return array
	 */
	public function getRelationsFieldsConfig(Array $attrConfig, $_only_struct, $viewType)
	{
		$this->setBehaviorAttributes(false);
		if(!isset(self::$_hasBehaviorAttributes[$this->getOwnerClassName()][$attrConfig['varname']]))
		{
			throw new CException('В поведении модели "'.$this->getOwnerClassName().'" нет конфигурации для атрибута "'.$attrConfig['varname'].'"');
		}
		return $this->generateRelationsFieldsConfig($attrConfig, $_only_struct, $viewType, self::$_hasBehaviorAttributes[$this->getOwnerClassName()][$attrConfig['varname']]);
	}

	/**
	 * Установка атрибутов поведения
	 * @access private
	 * @param bool $ckeck Default=false; Необходимо ли проверять 
	 */
	private function setBehaviorAttributes($check=false)
	{
		if(empty(self::$_hasBehaviorAttributes[$this->getOwnerClassName()]))
		{
			foreach($this->getOwner()->behaviors() as $v)
			{
				$_class = explode('.',$v['class']);
				if(end($_class)==__CLASS__)
				{
					// INFO: Чтобы знать какие данному поведению установлены атрибуты
					self::$_hasBehaviorAttributes[$this->getOwnerClassName()][$v['showInAttribute']] = $v;
					if($check && isset(self::$_hasBehaviorAttributes[$v['showInAttribute']]))
					{
						$_m=null;
						if(YII_DEBUG) $_m = ' указанное в свойстве "showInAttribute" поведения "'.__CLASS__.'"';
						throw new CException('У модели не может быть более одного атрибута со значением "'.$v['showInAttribute'].'"'.$_m.'.');
					}
				}
			}
		}
	}

	/**
	 * Сеттер для {@link _ownerSetModel}
	 * Так же проверяет на существование переданного класса
	 * @access public
	 * @param string $value
	 */
	public function setOwnerSetModel($value)
	{
		$this->_ownerSetModel = $value;
		if(!Y::class_exists($this->_ownerSetModel))
		{
			$_m=null;
			if(YII_DEBUG)
			{
				$_m = ' См.: '.__CLASS__.'ownerSetModel';
			}
			throw new CException('Подставленный класс "'.$this->_ownerSetModel.'" не существует.'.$_m);
		}
	}

	/**
	 * Геттер для {@link _ownerSetModel}
	 * @access public
	 * @return string
	 */
	public function getOwnerSetModel()
	{
		if(!$this->_ownerSetModel)
		{
			return $this->getOwnerClassName();
		}
		return $this->_ownerSetModel;
	}

	/**
	 * Генерация fildsConfig для каждого объявленного атрибута в owner модели
	 * Используется методом {@link getRelationsFieldsConfig}
	 * @access private
	 * @param Array $attrConfig Передается из метода {@link getRelationsFieldsConfig}
	 * @param bool $_only_struct Передается из метода {@link getRelationsFieldsConfig}
	 * @param int $viewType Передается из метода {@link getRelationsFieldsConfig}
	 * @return array
	 */
	private function generateRelationsFieldsConfig($attrConfig, $_only_struct, $viewType, $behaviorAttr)
	{
		$_return = $attrConfig;
		// setting label if not exists
		if(empty($_return['label']))
		{
			$_return['label'] = $this->getShowInAttributeLabel($attrConfig['varname']);
		}

		// setting options
		$_options = [
			'joinType'		=> empty($behaviorAttr['joinType']) ? self::JOINMULTI : $behaviorAttr['joinType'],
			'fieldsConfig'	=> $this->getInheritedFieldsConfig($_only_struct, $viewType, $behaviorAttr),
		];
		if(empty($_return['options']))
		{
			$_return['options'] = $_options;
		}
		else
		{
			$_return['options'] = CMap::mergeArray($_return['options'], $_options);
		}

		return $_return;

	}

	/**
	 * Получить getFieldsConfig реузльтаты inherited модели для дальнейшей передачи 
	 * @access private
	 * @param bool $_only_struct Передается из метода {@link generateRelationsFieldsConfig}
	 * @param int $viewType Передается из метода {@link generateRelationsFieldsConfig}
	 * @return array
	 */
	private function getInheritedFieldsConfig($_only_struct, $viewType, $behaviorAttr)
	{
		$_config = MActiveRecord::model($behaviorAttr['inheritedClass'])->getFieldsConfig($_only_struct, $viewType);
		$_config = $_config[ key($_config) ]['attributes'];
		return $_config;
	}

	/**
	 * Проверка наличия необходимых атрибутов/полей у inherited модели
	 * Если атрибута нет, выбрасывается CException
	 * Проверяются:
	 * 		showInAttribute
	 * 		inheritedClass
	 * 		attrModel
	 * 		attrModelPk
	 * 		attrModelAttr
	 * @access private
	 * @param string $name Название атрибута, если не указывать, проверят все
	 */
	private function checkAttributesExists($name=null)
	{
		if(empty($this->showInAttribute))
		{
			$_m=null;
			if(YII_DEBUG)
			{
				$_m = ' Установите параметр "showInAttribute" для модели "'.$this->getOwnerClassName().'" в методе behaviors().';
			}
			throw new CException('Не установлено значение свойства showInAttribute.'.$_m);
		}
		if ( empty($this->inheritedClass) ) 
		{
			$_m=null;
			if(YII_DEBUG)
			{
				$_m = ' Установите параметр "inheritedClass" в разделе с "showInAttribute"=>"'.$this->showInAttribute.'" для модели "'.$this->getOwnerClassName().'" в методе behaviors().';
			}
			throw new CException('Не установлено значение свойства inheritedClass.'.$_m);
		}

		// TODO: Описать
		$this->setBehaviorAttributes(true);

		/**
		 * Проверка единицы атрибута
		 * @param string $attrName Название атрибута, который должен присутсвовать у inherited модели
		 */
		$_checkOwnerAttributeExitst = function($attrName) 
		{
			if(!MActiveRecord::model($this->getInheritedClassName())->hasAttribute($attrName))
				throw new CException('У модели "'.$this->inheritedClass.'" не обнаружен атрибут "'.$attrName.'" для связи типа Relation*');
		};

		if($name===null)
		{
			$_checkOwnerAttributeExitst($this->attrModel);
			$_checkOwnerAttributeExitst($this->attrModelPk);
			$_checkOwnerAttributeExitst($this->attrModelAttr);
		}
		else
		{
			$_checkOwnerAttributeExitst($name);
		}
	}

	/**
	 * Геттер
	 * Получить/сгенерировать label для конкретного showInAttribute
	 * Используется в {@link generateRelationsFieldsConfig}
	 * @access public
	 * @param string $for Для какого showInAttribute
	 * @return string
	 */
	public function getShowInAttributeLabel($for)
	{
		if(!isset($this->_showInAttributeLabel[$for]))
		{
			$this->_showInAttributeLabel[$for] = ucfirst(str_replace('_',' ', $this->showInAttribute));
		}
		return $this->_showInAttributeLabel[$for];
	}

	/**
	 * Геттер.
	 * Получить идентификатор owner модели
	 * @access public
	 * @return int
	 */
	public function getOwnerPk()
	{
		return $this->getOwner()->getPrimaryKey();
	}

	/**
	 * Старт транзакции в {@link afterConstruct}
	 * @access private
	 */
	private function startTransaction()
	{
		if(!Y::isTransactionOn() && empty(self::$_transaction)) 
		{
			self::$_transaction = Yii::app()->getDb()->beginTransaction();
			if(empty(self::$_transaction))
			{
				// Если по каким то причиная не зпустилось
				throw new CException('Невозможно определить транзакции!');
			}
		}
	}

	/**
	 * Произвести действие я странзакцией
	 * @access private
	 * @param Bool $act
	 */
	private function doTransaction($act)
	{
		if(self::$_transaction===null) return false;
		if($act) 
		{
			self::$_transaction->commit();
			self::$_transaction=null;
			// $this->log("T R A N S A C T I O N  - - -  C O M M I T");
			// $this->log(var_export(self::$_toFinishTransaction,true), false);
		}
		else 
		{
			self::$_transaction->rollBack();
			self::$_transaction=null;
			// $this->log("T R A N S A C T I O N  - - -  R O L L B A C K");
			// $this->log(var_export(self::$_toFinishTransaction,true), false);
		}
	}

	/**
	 * Устанавливаем, можно ли завершить транзакцию
	 * Вызывает {@link doTransaction}
	 * @access private
	 */
	private function canEndTransaction()
	{
		// TODO: via exception if not seted "all"
		if(!isset(self::$_toFinishTransaction['for_transaction_end']['all']))
		{
			$_m=null;
			if(YII_DEBUG)
			{
				$_m =' Некорректно установлен (либо отсутствует) флаг _toFinishTransaction[for_transaction_end][all].';
			}
			throw new CException("Неправильно определне флаг для завершения транзакции.".$_m);
		}
		$_cAll		= count(self::$_toFinishTransaction['for_transaction_end']['all']);
		$_cSuccess	= isset(self::$_toFinishTransaction['for_transaction_end']['success']) ? count(self::$_toFinishTransaction['for_transaction_end']['success']) : 0;
		$_cFail		= isset(self::$_toFinishTransaction['for_transaction_end']['fail']) ? count(self::$_toFinishTransaction['for_transaction_end']['fail']) : 0;
		
		if($_cAll == $_cSuccess)
		{
			$this->doTransaction(true);
		}
		elseif( ($_cFail+$_cSuccess) == $_cAll ) 
		{
			$this->doTransaction(false);
		}
		
	}

	/**
	 * Логирование в файл используя {@link Y::toLogFile}
	 * @access private
	 * @param string $data Данные для записи
	 * @param bool $showD Нужно ли показыать "заголовок"
	 */
	private function log($data, $showD = true)
	{
		$_d = debug_backtrace(false, 2);
		if($showD) Y::toLogFile("\n=================== from_f: ".$_d[1]['function'].":".$_d[1]['line']."; ===================\n", 'BehaviorTest');
		Y::toLogFile($data."\n", 'BehaviorTest');
	}

	/**
	 * Вывод в лог-файл трейс вызова метода
	 * Исключает собственный вызов
	 * Использет для вывода {@link log}
	 * @access private
	 * @param int $num Глубина обратного вызова методов
	 */
	private function logFuncCall($num=9)
	{
		$_d = debug_backtrace(false, $num);
		$_s = array();
		for($i=1; $i<$num; $i++)
		{
			if(!isset($_d[$i])) continue;
			$_s[] = str_repeat("  ", $i)
				.$_d[$i]['class']
				.'::'.$_d[$i]['function']
				.(isset($_d[$i]['line']) ? ':'.$_d[$i]['line'] : '')
				."\n";
		}
		$this->log('----> '.implode(' <- ', $_s));
	}

	/**
	 * Создает "флаговый" массив для корректного завершения транзакции
	 * Формируется из {@link _inheritedModels} текущей owner модели
	 * Вызывается из:
	 * 	{@link beforeSave}
	 * 	{@link beforeDelete}
	 * 	{@link setInheritedModels}
	 * Использует:
	 * 	{@link setForTransAll}
	 */
	private function generateTransForAll($fix=null)
	{
		if($this->joinType==self::JOINSINGLE)
		{
			$this->setForTransAll($this->_inheritedModels,0, $fix);
		}
		elseif($this->joinType==self::JOINMULTI)
		{
			// INFO: см.выше, только для multiple inherited модели
			foreach($this->_inheritedModels as $index => $inheritedValue)
			{
				$this->setForTransAll($this->_inheritedModels[$index], $index,$fix);
			}
		}
	}

	/**
	 * Унификация заиписи для завершения просецца транзакции
	 * Используется только в {@link generateTransForAll}
	 * !Важно:
	 * Исключает дублирование записи
	 * 	Зависимости:
	 * 		{@link MActiveRecord::ARRelationsToTransactionCkeck}
	 * 		{@link _createIndexForInherited}
	 * @access private
	 * @param Object	$model	Модель у который необходимо получить запись
	 * @param int		$index	Индекс модели в {@link _inheritedModels}
	 * @param string	$pos	Простой текст который можно добавить для анализа
	 */
	private function setForTransAll($model, $index, $pos=null)
	{
		if(empty($model->ARRelationsToTransactionCkeck))
		{
			self::$_toFinishTransaction['for_transaction_end']['all'][] = $this->_createIndexForInherited($model, ($pos ? $index."($pos)" : $index));
		}
		$model->ARRelationsToTransactionCkeck = $this->_createIndexForInherited($model, ($pos ? $index."($pos)" : $index));
	}
}