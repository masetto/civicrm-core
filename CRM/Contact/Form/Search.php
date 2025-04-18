<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Base Search / View form for *all* listing of multiple
 * contacts
 */
class CRM_Contact_Form_Search extends CRM_Core_Form_Search {

  /**
   * list of valid contexts.
   *
   * @var array
   */
  public static $_validContext = NULL;

  /**
   * List of values used when we want to display other objects.
   *
   * @var array
   */
  public static $_modeValues = NULL;

  /**
   * The contextMenu.
   *
   * @var array
   */
  protected $_contextMenu;

  /**
   * The groupId retrieved from the GET vars.
   *
   * @var int
   */
  public $_groupID;

  /**
   * The Group ID belonging to Add Member to group ID.
   * retrieved from the GET vars
   *
   * @var int
   */
  protected $_amtgID;

  /**
   * The saved search ID retrieved from the GET vars.
   *
   * @var int
   */
  protected $_ssID;

  /**
   * The group elements.
   *
   * @var array
   */
  public $_group;
  public $_groupElement;

  /**
   * The tag elements.
   *
   * @var array
   */
  public $_tag;

  /**
   * The params used for search.
   *
   * @var array
   */
  protected $_params;

  /**
   * The return properties used for search.
   *
   * @var array
   */
  protected $_returnProperties;

  /**
   * The sort by character.
   *
   * @var string
   */
  protected $_sortByCharacter;

  /**
   * The profile group id used for display.
   *
   * @var int
   */
  protected $_ufGroupID;

  /**
   * Csv - common search values
   *
   * @var array
   */
  public static $csv = ['contact_type', 'group', 'tag'];

  /**
   * How to display the results. Should we display as contributons, members, cases etc.
   *
   * @var string
   */
  protected $_componentMode;

  /**
   * What operator should we use, AND or OR.
   *
   * @var string
   */
  protected $_operator;

  protected $_modeValue;

  /**
   * Declare entity reference fields as they will need to be converted to using 'IN'.
   *
   * @var array
   */
  protected $entityReferenceFields = ['event_id', 'membership_type_id'];

  /**
   * Name of the selector to use.
   * @var string
   */
  public static $_selectorName = 'CRM_Contact_Selector';
  protected $_customSearchID = NULL;
  protected $_customSearchClass = NULL;

  protected $_openedPanes = [];

  public function __construct($state = NULL, $action = CRM_Core_Action::NONE, $method = 'post', $name = NULL) {
    parent::__construct($state, $action, $method, $name);
    // Because this is a static variable, reset it in case it got changed elsewhere.
    // Should only come up during unit tests.
    // Note the only subclass that seems to set this does it in preprocess (custom searches)
    self::$_selectorName = 'CRM_Contact_Selector';
  }

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'Contact';
  }

  /**
   * Define the set of valid contexts that the search form operates on.
   *
   * @return array
   *   the valid context set and the titles
   */
  public static function &validContext() {
    if (!(self::$_validContext)) {
      self::$_validContext = [
        'smog' => 'Show members of group',
        'amtg' => 'Add members to group',
        'basic' => 'Basic Search',
        'search' => 'Search',
        'builder' => 'Search Builder',
        'advanced' => 'Advanced Search',
        'custom' => 'Custom Search',
      ];
    }
    return self::$_validContext;
  }

  /**
   * @param $context
   *
   * @return bool
   */
  public static function isSearchContext($context) {
    $searchContext = self::validContext()[$context] ?? FALSE;
    return (bool) $searchContext;
  }

  public static function setModeValues(): void {
    self::$_modeValues = [
      CRM_Contact_BAO_Query::MODE_CONTACTS => [
        'selectorName' => self::$_selectorName,
        'selectorLabel' => ts('Contacts'),
        'taskFile' => 'CRM/Contact/Form/Search/ResultTasks.tpl',
        'taskContext' => NULL,
        'resultFile' => 'CRM/Contact/Form/Selector.tpl',
        'resultContext' => NULL,
        'taskClassName' => 'CRM_Contact_Task',
        'component' => '',
      ],
      CRM_Contact_BAO_Query::MODE_CONTRIBUTE => [
        'selectorName' => 'CRM_Contribute_Selector_Search',
        'selectorLabel' => ts('Contributions'),
        'taskFile' => 'CRM/common/searchResultTasks.tpl',
        'taskContext' => 'Contribution',
        'resultFile' => 'CRM/Contribute/Form/Selector.tpl',
        'resultContext' => 'Search',
        'taskClassName' => 'CRM_Contribute_Task',
        'component' => 'CiviContribute',
        'contributionSummary' => [],
      ],
      CRM_Contact_BAO_Query::MODE_EVENT => [
        'selectorName' => 'CRM_Event_Selector_Search',
        'selectorLabel' => ts('Event Participants'),
        'taskFile' => 'CRM/common/searchResultTasks.tpl',
        'taskContext' => NULL,
        'resultFile' => 'CRM/Event/Form/Selector.tpl',
        'resultContext' => 'Search',
        'taskClassName' => 'CRM_Event_Task',
        'component' => 'CiviEvent',
      ],
      CRM_Contact_BAO_Query::MODE_ACTIVITY => [
        'selectorName' => 'CRM_Activity_Selector_Search',
        'selectorLabel' => ts('Activities'),
        'taskFile' => 'CRM/common/searchResultTasks.tpl',
        'taskContext' => NULL,
        'resultFile' => 'CRM/Activity/Form/Selector.tpl',
        'resultContext' => 'Search',
        'taskClassName' => 'CRM_Activity_Task',
        'component' => 'activity',
      ],
      CRM_Contact_BAO_Query::MODE_MEMBER => [
        'selectorName' => 'CRM_Member_Selector_Search',
        'selectorLabel' => ts('Memberships'),
        'taskFile' => "CRM/common/searchResultTasks.tpl",
        'taskContext' => NULL,
        'resultFile' => 'CRM/Member/Form/Selector.tpl',
        'resultContext' => 'Search',
        'taskClassName' => 'CRM_Member_Task',
        'component' => 'CiviMember',
      ],
      CRM_Contact_BAO_Query::MODE_CASE => [
        'selectorName' => 'CRM_Case_Selector_Search',
        'selectorLabel' => ts('Cases'),
        'taskFile' => "CRM/common/searchResultTasks.tpl",
        'taskContext' => NULL,
        'resultFile' => 'CRM/Case/Form/Selector.tpl',
        'resultContext' => 'Search',
        'taskClassName' => 'CRM_Case_Task',
        'component' => 'CiviCase',
      ],
      CRM_Contact_BAO_Query::MODE_CONTACTSRELATED => [
        'selectorName' => self::$_selectorName,
        'selectorLabel' => ts('Related Contacts'),
        'taskFile' => 'CRM/Contact/Form/Search/ResultTasks.tpl',
        'taskContext' => NULL,
        'resultFile' => 'CRM/Contact/Form/Selector.tpl',
        'resultContext' => NULL,
        'taskClassName' => 'CRM_Contact_Task',
        'component' => 'related_contact',
      ],
      CRM_Contact_BAO_Query::MODE_MAILING => [
        'selectorName' => 'CRM_Mailing_Selector_Search',
        'selectorLabel' => ts('Mailings'),
        'taskFile' => "CRM/common/searchResultTasks.tpl",
        'taskContext' => NULL,
        'resultFile' => 'CRM/Mailing/Form/Selector.tpl',
        'resultContext' => 'Search',
        'taskClassName' => 'CRM_Mailing_Task',
        'component' => 'CiviMail',
      ],
    ];
  }

  /**
   * Get the metadata for the query mode (this includes task class names)
   *
   * @param int $mode
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public static function getModeValue($mode = CRM_Contact_BAO_Query::MODE_CONTACTS) {
    $searchPane = CRM_Utils_Request::retrieve('searchPane', 'String');
    if (!empty($searchPane)) {
      $mode = array_search($searchPane, self::getModeToComponentMapping());
    }

    self::setModeValues();
    // Note $mode might === FALSE because array_search above failed, e.g. for searchPane='location'
    if (empty(self::$_modeValues[$mode])) {
      $mode = CRM_Contact_BAO_Query::MODE_CONTACTS;
    }

    return self::$_modeValues[$mode];
  }

  /**
   * Get a mapping of modes to components.
   *
   * This will map the integers to the components. Contact has an empty component
   * an pseudo-components exist for activity & related_contact.
   *
   * @return array
   */
  public static function getModeToComponentMapping() {
    $mapping = [];
    self::setModeValues();

    foreach (self::$_modeValues as $id => $metadata) {
      $mapping[$id] = $metadata['component'];
    }
    return $mapping;
  }

  /**
   * @return array
   */
  public static function getModeSelect() {
    self::setModeValues();

    $enabledComponents = CRM_Core_Component::getEnabledComponents();
    $componentModes = [];
    foreach (self::$_modeValues as $id => & $value) {
      if (str_contains($value['component'], 'Civi')
        && !array_key_exists($value['component'], $enabledComponents)
      ) {
        continue;
      }
      $componentModes[$id] = $value['selectorLabel'];
    }

    // unset disabled components
    if (!array_key_exists('CiviMail', $enabledComponents)) {
      unset($componentModes[CRM_Contact_BAO_Query::MODE_MAILING]);
    }

    // unset contributions or participants if user does not have permission on them
    if (!CRM_Core_Permission::access('CiviContribute')) {
      unset($componentModes[CRM_Contact_BAO_Query::MODE_CONTRIBUTE]);
    }

    if (!CRM_Core_Permission::access('CiviEvent')) {
      unset($componentModes[CRM_Contact_BAO_Query::MODE_EVENT]);
    }

    if (!CRM_Core_Permission::access('CiviMember')) {
      unset($componentModes[CRM_Contact_BAO_Query::MODE_MEMBER]);
    }

    if (!CRM_Core_Permission::check('view all activities')) {
      unset($componentModes[CRM_Contact_BAO_Query::MODE_ACTIVITY]);
    }

    return $componentModes;
  }

  /**
   * Builds the list of tasks or actions that a searcher can perform on a result set.
   *
   * @return array
   */
  public function buildTaskList() {
    // amtg = 'Add members to group'
    if ($this->_context !== 'amtg') {
      $taskParams['deletedContacts'] = FALSE;
      if ($this->_componentMode == CRM_Contact_BAO_Query::MODE_CONTACTS || $this->_componentMode == CRM_Contact_BAO_Query::MODE_CONTACTSRELATED) {
        $taskParams['deletedContacts'] = $this->_formValues['deleted_contacts'] ?? NULL;
      }
      $className = $this->_modeValue['taskClassName'];
      $taskParams['ssID'] = $this->_ssID ?? NULL;
      $this->_taskList += $className::permissionedTaskTitles(CRM_Core_Permission::getPermission(), $taskParams);
    }

    return $this->_taskList;
  }

  /**
   * Build the common elements between the search/advanced form.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    // some tasks.. what do we want to do with the selected contacts ?
    $this->_taskList = $this->buildTaskList();

    if (isset($this->_ssID)) {
      $search_custom_id
        = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_SavedSearch', $this->_ssID, 'search_custom_id');

      $savedSearchValues = [
        'id' => $this->_ssID,
        'name' => CRM_Contact_BAO_SavedSearch::getName($this->_ssID, 'title'),
        'search_custom_id' => $search_custom_id,
      ];
    }
    $this->assign('savedSearch', $savedSearchValues ?? NULL);
    $this->assign('ssID', $this->_ssID);

    if ($this->_context === 'smog') {
      // CRM-11788, we might want to do this for all of search where force=1
      $formQFKey = $this->_formValues['qfKey'] ?? NULL;
      $getQFKey = $_GET['qfKey'] ?? NULL;
      $postQFKey = $_POST['qfKey'] ?? NULL;
      if ($formQFKey && empty($getQFKey) && empty($postQFKey)) {
        $url = CRM_Utils_System::makeURL('qfKey') . $formQFKey;
        CRM_Utils_System::redirect($url);
      }
      $permissionForGroup = FALSE;

      if (!empty($this->_groupID)) {
        // check if user has permission to edit members of this group
        $permission = CRM_Contact_BAO_Group::checkPermission($this->_groupID);
        if ($permission && in_array(CRM_Core_Permission::EDIT, $permission)) {
          $permissionForGroup = TRUE;
        }

        // check if _groupID exists, it might not if
        // we are displaying a hidden group
        if (!isset($this->_group[$this->_groupID])) {
          $this->_group[$this->_groupID]
            = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group', $this->_groupID, 'title');
        }

        // set the group title
        $groupValues = ['id' => $this->_groupID, 'title' => $this->_group[$this->_groupID]];
        $this->assign('group', $groupValues);

        // also set ssID if this is a saved search
        $ssID = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group', $this->_groupID, 'saved_search_id');
        $this->assign('ssID', $ssID);

        $this->_ssID = $ssID;
        $this->assign('editSmartGroupURL', $ssID ? CRM_Contact_BAO_SavedSearch::getEditSearchUrl($ssID) : FALSE);

        // Set dynamic page title for 'Show Members of Group'
        $this->setTitle(ts('Contacts in Group: %1', [1 => $this->_group[$this->_groupID]]));
      }

      $group_contact_status = [];
      foreach (CRM_Core_SelectValues::groupContactStatus() as $k => $v) {
        if (!empty($k)) {
          $group_contact_status[] = $this->createElement('checkbox', $k, NULL, $v);
        }
      }
      $this->addGroup($group_contact_status,
        'group_contact_status', ts('Group Status')
      );

      $this->assign('permissionEditSmartGroup', CRM_Core_Permission::check('edit groups'));
      $this->assign('permissionedForGroup', $permissionForGroup);
    }

    // add the go button for the action form, note it is of type 'next' rather than of type 'submit'
    if ($this->_context === 'amtg') {
      // check if _groupID exists, it might not if
      // we are displaying a hidden group
      if (!isset($this->_group[$this->_amtgID])) {
        $this->assign('permissionedForGroup', FALSE);
        $this->_group[$this->_amtgID]
          = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group', $this->_amtgID, 'title');
      }

      // Set dynamic page title for 'Add Members Group'
      $this->setTitle(ts('Add to Group: %1', [1 => $this->_group[$this->_amtgID]]));
      // also set the group title and freeze the action task with Add Members to Group
      $groupValues = ['id' => $this->_amtgID, 'title' => $this->_group[$this->_amtgID]];
      $this->assign('group', $groupValues);
      $this->add('xbutton', $this->_actionButtonName, ts('Add Contacts to %1', [1 => $this->_group[$this->_amtgID]]),
        [
          'type' => 'submit',
          'class' => 'crm-form-submit',
        ]
      );
      $this->add('hidden', 'task', CRM_Contact_Task::GROUP_ADD);
      $selectedRowsRadio = $this->addElement('radio', 'radio_ts', NULL, '', 'ts_sel', ['checked' => 'checked']);
      $allRowsRadio = $this->addElement('radio', 'radio_ts', NULL, '', 'ts_all');
      $this->assign('ts_sel_id', $selectedRowsRadio->_attributes['id']);
      $this->assign('ts_all_id', $allRowsRadio->_attributes['id']);
    }

    $selectedContactIds = [];
    $qfKeyParam = $this->_formValues['qfKey'] ?? NULL;
    // We use ajax to handle selections only if the search results component_mode is set to "contacts"
    if ($qfKeyParam && ($this->get('component_mode') <= CRM_Contact_BAO_Query::MODE_CONTACTS || $this->get('component_mode') == CRM_Contact_BAO_Query::MODE_CONTACTSRELATED)) {
      $this->addClass('crm-ajax-selection-form');
      $qfKeyParam = "civicrm search {$qfKeyParam}";
      $selectedContactIdsArr = Civi::service('prevnext')->getSelection($qfKeyParam);
      $selectedContactIds = array_keys($selectedContactIdsArr[$qfKeyParam]);
    }

    $this->assign('selectedContactIds', $selectedContactIds);

    $rows = $this->get('rows');

    if (is_array($rows)) {
      $this->addRowSelectors($rows);
    }

  }

  /**
   * Processing needed for buildForm and later.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    // set the various class variables

    $this->_group = CRM_Core_PseudoConstant::group();

    $this->_tag = CRM_Core_BAO_Tag::getTags();
    $this->_done = FALSE;

    /*
     * we allow the controller to set force/reset externally, useful when we are being
     * driven by the wizard framework
     */

    $this->_reset = CRM_Utils_Request::retrieve('reset', 'Boolean');

    $this->_force = CRM_Utils_Request::retrieve('force', 'Boolean');
    $this->_groupID = CRM_Utils_Request::retrieve('gid', 'Positive', $this);
    $this->_amtgID = CRM_Utils_Request::retrieve('amtgID', 'Positive', $this);
    $this->_ssID = CRM_Utils_Request::retrieve('ssID', 'Positive', $this);
    $this->_sortByCharacter = CRM_Utils_Request::retrieve('sortByCharacter', 'String', $this);
    $ufGroupID = CRM_Utils_Request::retrieve('uf_group_id', 'Positive', $this);
    $this->_componentMode = CRM_Utils_Request::retrieve('component_mode', 'Positive', $this, FALSE, CRM_Contact_BAO_Query::MODE_CONTACTS, $_REQUEST);
    $this->_operator = CRM_Utils_Request::retrieve('operator', 'String', $this, FALSE, CRM_Contact_BAO_Query::SEARCH_OPERATOR_AND, 'REQUEST');

    if (!empty($this->_ssID) && !CRM_Core_Permission::check('edit groups')) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to modify smart groups'));
    }

    /**
     * set the button names
     */
    $this->_actionButtonName = $this->getButtonName('next', 'action');

    $this->assign('actionButtonName', $this->_actionButtonName);

    // if we dont get this from the url, use default if one exsts
    $config = CRM_Core_Config::singleton();
    if ($ufGroupID == NULL &&
      $config->defaultSearchProfileID != NULL
    ) {
      $ufGroupID = $config->defaultSearchProfileID;
    }

    // assign context to drive the template display, make sure context is valid
    $this->_context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this, FALSE, 'search');
    if (!array_key_exists($this->_context, self::validContext())) {
      $this->_context = 'search';
    }
    $this->set('context', $this->_context);
    $this->assign('context', $this->_context);

    $this->_modeValue = self::getModeValue($this->_componentMode);
    $this->assign($this->_modeValue);

    $this->set('selectorName', self::$_selectorName);

    // get user submitted values
    // get it from controller only if form has been submitted, else preProcess has set this
    // $this->controller->isModal( ) returns TRUE if page is
    // valid, i.e all the validations are TRUE

    if (!empty($_POST) && !$this->controller->isModal()) {
      $this->_formValues = $this->controller->exportValues($this->_name);

      $this->normalizeFormValues();
      $this->_params = CRM_Contact_BAO_Query::convertFormValues($this->_formValues, 0, FALSE, NULL, $this->entityReferenceFields);
      $this->_returnProperties = &$this->returnProperties();

      // also get the uf group id directly from the post value
      $ufGroupID = $_POST['uf_group_id'] ?? $ufGroupID;
      $this->_formValues['uf_group_id'] = $ufGroupID;
      $this->set('uf_group_id', $ufGroupID);

      // also get the object mode directly from the post value
      $this->_componentMode = $_POST['component_mode'] ?? $this->_componentMode;

      // also get the operator from the post value if set
      $this->_operator = $_POST['operator'] ?? $this->_operator;
      $this->_formValues['operator'] = $this->_operator;
      $this->set('operator', $this->_operator);
    }
    else {
      $this->_formValues = $this->get('formValues');
      $this->_params = CRM_Contact_BAO_Query::convertFormValues($this->_formValues, 0, FALSE, NULL, $this->entityReferenceFields);
      $this->_returnProperties = &$this->returnProperties();
      if ($ufGroupID) {
        $this->set('uf_group_id', $ufGroupID);
      }
    }

    if (empty($this->_formValues)) {
      //check if group is a smart group (fix for CRM-1255)
      if ($this->_groupID) {
        if ($ssId = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group', $this->_groupID, 'saved_search_id')) {
          $this->_ssID = $ssId;
        }
      }

      // fix for CRM-1907
      if (isset($this->_ssID) && $this->_context !== 'smog') {
        // we only retrieve the saved search values if out current values are null
        $this->_formValues = CRM_Contact_BAO_SavedSearch::getFormValues($this->_ssID);

        //fix for CRM-1505
        if (CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_SavedSearch', $this->_ssID, 'mapping_id')) {
          $this->_params = CRM_Contact_BAO_SavedSearch::getSearchParams($this->_ssID);
        }
        else {
          $this->_params = CRM_Contact_BAO_Query::convertFormValues($this->_formValues);
        }
        $this->_returnProperties = &$this->returnProperties();
      }
      else {
        if ($ufGroupID) {
          // also set the uf group id if not already present
          $this->_formValues['uf_group_id'] = $ufGroupID;
        }
        if (isset($this->_componentMode)) {
          $this->_formValues['component_mode'] = $this->_componentMode;
        }
        if (isset($this->_operator)) {
          $this->_formValues['operator'] = $this->_operator;
        }

        // FIXME: we should generalise in a way that components could inject url-filters
        // just like they build their own form elements
        foreach ([
          'mailing_id',
          'mailing_delivery_status',
          'mailing_open_status',
          'mailing_click_status',
          'mailing_reply_status',
          'mailing_optout',
          'mailing_unsubscribe',
          'mailing_date_low',
          'mailing_date_high',
          'mailing_job_start_date_low',
          'mailing_job_start_date_high',
          'mailing_job_start_date_relative',
        ] as $mailingFilter) {
          $type = 'String';
          if ($mailingFilter == 'mailing_id' &&
            $filterVal = CRM_Utils_Request::retrieve('mailing_id', 'Positive', $this)
          ) {
            $this->_formValues[$mailingFilter] = [$filterVal];
          }
          elseif ($filterVal = CRM_Utils_Request::retrieve($mailingFilter, $type, $this)) {
            $this->_formValues[$mailingFilter] = $filterVal;
          }
          if ($filterVal) {
            $this->_openedPanes['Mailings'] = 1;
            $this->_formValues['hidden_CiviMail'] = 1;
          }
        }
      }
    }
    $this->assign('id', $ufGroupID);
    $operator = $this->_formValues['operator'] ?? CRM_Contact_BAO_Query::SEARCH_OPERATOR_AND;
    $this->set('queryOperator', $operator);
    if ($operator == CRM_Contact_BAO_Query::SEARCH_OPERATOR_OR) {
      $this->assign('operator', ts('OR'));
    }
    else {
      $this->assign('operator', ts('AND'));
    }

    // show the context menu only when we’re not searching for deleted contacts; CRM-5673
    if (empty($this->_formValues['deleted_contacts'])) {
      $menuItems = CRM_Contact_BAO_Contact::contextMenu();
      $primaryActions = $menuItems['primaryActions'] ?? [];
      $this->_contextMenu = $menuItems['moreActions'] ?? [];
      $this->assign('contextMenu', $primaryActions + $this->_contextMenu);
    }

    if (!isset($this->_componentMode)) {
      $this->_componentMode = CRM_Contact_BAO_Query::MODE_CONTACTS;
    }
    self::$_selectorName = $this->_modeValue['selectorName'];
    self::setModeValues();

    $setDynamic = FALSE;
    if (str_contains(self::$_selectorName, 'CRM_Contact_Selector')) {
      $selector = new self::$_selectorName(
        $this->_customSearchClass,
        $this->_formValues,
        $this->_params,
        $this->_returnProperties,
        $this->_action,
        FALSE, TRUE,
        $this->_context,
        $this->_contextMenu
      );
      $setDynamic = TRUE;
    }
    else {
      $selector = new self::$_selectorName(
        $this->_params,
        $this->_action,
        NULL, FALSE, NULL,
        "search", "advanced"
      );
    }

    $selector->setKey($this->controller->_key);

    $controller = new CRM_Contact_Selector_Controller($selector,
      $this->get(CRM_Utils_Pager::PAGE_ID),
      $this->get(CRM_Utils_Sort::SORT_ID),
      CRM_Core_Action::VIEW,
      $this,
      CRM_Core_Selector_Controller::TRANSFER
    );
    $controller->setEmbedded(TRUE);
    $controller->setDynamicAction($setDynamic);

    if ($this->_force) {
      $this->loadMetadata();
      $this->postProcess();

      /*
       * Note that we repeat this, since the search creates and stores
       * values that potentially change the controller behavior. i.e. things
       * like totalCount etc
       */
      $controller = new CRM_Contact_Selector_Controller($selector,
        $this->get(CRM_Utils_Pager::PAGE_ID),
        $this->getSortID(),
        CRM_Core_Action::VIEW, $this, CRM_Core_Selector_Controller::TRANSFER
      );
      $controller->setEmbedded(TRUE);
      $controller->setDynamicAction($setDynamic);
    }

    $controller->moveFromSessionToTemplate();
  }

  /**
   * Common post processing.
   */
  public function postProcess() {
    /*
     * sometime we do a postProcess early on, so we dont need to repeat it
     * this will most likely introduce some more bugs :(
     */

    if ($this->_done) {
      return;
    }
    $this->_done = TRUE;

    //for prev/next pagination
    $crmPID = CRM_Utils_Request::retrieve('crmPID', 'Integer');

    //get the button name
    $buttonName = $this->controller->getButtonName();
    $this->_formValues['uf_group_id'] ??= $this->get('uf_group_id') ?: CRM_Core_Config::singleton()->defaultSearchProfileID;

    if (isset($this->_componentMode) && empty($this->_formValues['component_mode'])) {
      $this->_formValues['component_mode'] = $this->_componentMode;
    }

    if (isset($this->_operator) && empty($this->_formValues['operator'])) {
      $this->_formValues['operator'] = $this->_operator;
    }

    if (empty($this->_formValues['qfKey'])) {
      $this->_formValues['qfKey'] = $this->controller->_key;
    }

    if (!CRM_Core_Permission::check('access deleted contacts')) {
      unset($this->_formValues['deleted_contacts']);
    }

    $this->set('type', $this->_action);
    $this->set('formValues', $this->_formValues);
    $this->set('queryParams', $this->_params);
    $this->set('returnProperties', $this->_returnProperties);

    if ($buttonName == $this->_actionButtonName) {
      // check actionName and if next, then do not repeat a search, since we are going to the next page
      // hack, make sure we reset the task values
      $stateMachine = $this->controller->getStateMachine();
      $formName = $stateMachine->getTaskFormName();
      $this->controller->resetPage($formName);
      return;
    }
    else {
      if (array_key_exists($this->getButtonName('refresh'), $_POST) ||
        ($this->_force && !$crmPID)
      ) {
        //reset the cache table for new search
        $cacheKey = "civicrm search {$this->controller->_key}";
        Civi::service('prevnext')->deleteItem(NULL, $cacheKey);
      }
      $output = CRM_Core_Selector_Controller::SESSION;

      // create the selector, controller and run - store results in session
      $searchChildGroups = TRUE;
      if ($this->get('isAdvanced')) {
        $searchChildGroups = FALSE;
      }

      $setDynamic = FALSE;

      if (str_contains(self::$_selectorName, 'CRM_Contact_Selector')) {
        $selector = new self::$_selectorName(
          $this->_customSearchClass,
          $this->_formValues,
          $this->_params,
          $this->_returnProperties,
          $this->_action,
          FALSE,
          $searchChildGroups,
          $this->_context,
          $this->_contextMenu
        );
        $setDynamic = TRUE;
      }
      else {
        $selector = new self::$_selectorName(
          $this->_params,
          $this->_action,
          NULL,
          FALSE,
          NULL,
          "search",
          "advanced"
        );
      }

      $selector->setKey($this->controller->_key);

      // added the sorting  character to the form array
      $config = CRM_Core_Config::singleton();
      // do this only for contact search
      if ($setDynamic && $config->includeAlphabeticalPager) {
        // Don't recompute if we are just paging/sorting
        if ($this->_reset || (empty($_GET['crmPID']) && empty($_GET['crmSID']) && !$this->_sortByCharacter)) {
          $aToZBar = CRM_Utils_PagerAToZ::getAToZBar($selector, $this->_sortByCharacter);
          $this->set('AToZBar', $aToZBar);
        }
      }

      $controller = new CRM_Contact_Selector_Controller($selector,
        $this->get(CRM_Utils_Pager::PAGE_ID),
        $this->getSortID(),
        CRM_Core_Action::VIEW,
        $this,
        $output
      );
      $controller->setEmbedded(TRUE);
      $controller->setDynamicAction($setDynamic);
      $controller->run();
    }
  }

  /**
   * @return NULL
   */
  public function &returnProperties() {
    return CRM_Core_DAO::$_nullObject;
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   */
  public function getTitle() {
    return ts('Search');
  }

  /**
   * Check Access for a component
   * @param string $component
   * @return bool
   */
  protected static function checkComponentAccess($component) {
    $enabledComponents = CRM_Core_Component::getEnabledComponents();
    if (!array_key_exists($component, $enabledComponents)) {
      return FALSE;
    }
    return CRM_Core_Permission::access($component);
  }

  /**
   * Load metadata for fields on the form.
   *
   * @throws \CRM_Core_Exception
   */
  protected function loadMetadata() {
    // can't by pass acls by passing search criteria in the url.
    if (self::checkComponentAccess('CiviContribute')) {
      $this->addSearchFieldMetadata(['Contribution' => CRM_Contribute_BAO_Query::getSearchFieldMetadata()]);
      $this->addSearchFieldMetadata(['ContributionRecur' => CRM_Contribute_BAO_ContributionRecur::getContributionRecurSearchFieldMetadata()]);
    }
    if (self::checkComponentAccess('CiviPledge')) {
      $this->addSearchFieldMetadata(['Pledge' => CRM_Pledge_BAO_Query::getSearchFieldMetadata()]);
      $this->addSearchFieldMetadata(['PledgePayment' => CRM_Pledge_BAO_Query::getPledgePaymentSearchFieldMetadata()]);
    }
    if (self::checkComponentAccess('CiviEvent')) {
      $this->addSearchFieldMetadata(['Participant' => CRM_Event_BAO_Query::getSearchFieldMetadata()]);
    }
    if (self::checkComponentAccess('CiviMember')) {
      $this->addSearchFieldMetadata(['Membership' => CRM_Member_BAO_Query::getSearchFieldMetadata()]);
    }
    if (self::checkComponentAccess('CiviGrant')) {
      $this->addSearchFieldMetadata(['Grant' => CRM_Grant_BAO_Query::getSearchFieldMetadata()]);
    }
    if (self::checkComponentAccess('CiviCase')) {
      $this->addSearchFieldMetadata(['Case' => CRM_Case_BAO_Query::getSearchFieldMetadata()]);
    }
  }

}
