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

namespace Civi\Api4;

/**
 * EntityFinancialAccount. Joins financial accounts to financial types.
 *
 * @see https://docs.civicrm.org/dev/en/latest/financial/financialentities/#financial-accounts
 *
 * @bridge entity_id financial_account_id
 * @ui_join_filters account_relationship
 *
 * @package Civi\Api4
 */
class EntityFinancialAccount extends Generic\DAOEntity {
  use Generic\Traits\EntityBridge;

}
