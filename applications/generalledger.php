<?php
/**********************************************************************
    Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL,
	as published by the Free Software Foundation, either version 3
	of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
    See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
***********************************************************************/
class general_ledger_app extends application
{
	function __construct()
	{
		parent::__construct("GL", _($this->help_context = "&Banking and General Ledger"));

		$this->add_module(_("Transactions"));
		
		//Modified by spyrax10 21 Mar 2022
		$this->add_lapp_function(0, _("&Disbursement Entry"),
			"gl/inquiry/disbursement_list.php?", 'SA_DISBURSE_LIST', MENU_TRANSACTION
		);
		$this->add_lapp_function(0, _("&Receipts Entry"),
			"gl/inquiry/receipt_list.php?", 'SA_RECEIPT_LIST', MENU_TRANSACTION
		);

		$this->add_lapp_function(0, _("Re&mittance Entry"),
			"gl/inquiry/remittance_list.php?", 'SA_REMIT_INQ', MENU_TRANSACTION
		);
		//
		
		$this->add_lapp_function(0, _("Bank Account &Transfers"),
			"gl/bank_transfer.php?", 'SA_BANKTRANSFER', MENU_TRANSACTION
		);

		//Modified by spyrax10 21 Mar 2022	
		$this->add_rapp_function(0, _("&Journal Entry"),
			"gl/inquiry/journal_list.php?", 'SA_JE_LIST', MENU_TRANSACTION
		);
		//
		
		$this->add_rapp_function(0, _("&Budget Entry"),
			"gl/gl_budget.php?", 'SA_BUDGETENTRY', MENU_TRANSACTION);
		$this->add_rapp_function(0, _("&Reconcile Bank Account"),
			"gl/bank_account_reconcile.php?", 'SA_RECONCILE', MENU_TRANSACTION);
		$this->add_rapp_function(0, _("Revenue / &Costs Accruals"),
			"gl/accruals.php?", 'SA_ACCRUALS', MENU_TRANSACTION);

		$this->add_module(_("Inquiries and Reports"));
		//Added by spyrax10
		$this->add_lapp_function(1, _("&Banking Interbranch Inquiry"),
			"gl/inquiry/interbranch_list.php?", 'SA_INTERB_LIST', MENU_INQUIRY
		);
		//
		$this->add_lapp_function(1, _("&Journal Inquiry"),
			"gl/inquiry/journal_inquiry.php?", 'SA_GLANALYTIC_LIST', MENU_INQUIRY);
		
		//Modified by spyrax10 20 Jun 2022
		$this->add_lapp_function(1, _("GL &Inquiry"),
			"gl/inquiry/gl_account_inquiry.php?", 'SA_GL_LIST', MENU_INQUIRY
		);
		$this->add_lapp_function(1, _("Bank Account &Inquiry"),
			"gl/inquiry/bank_inquiry.php?", 'SA_BANK_LIST', MENU_INQUIRY
		);
		//

		$this->add_lapp_function(1, _("Ta&x Inquiry"),
			"gl/inquiry/tax_inquiry.php?", 'SA_TAXREP', MENU_INQUIRY);

		//Modified by spyrax10 24 Jun 2022
		$this->add_rapp_function(1, _("Trial &Balance"),
			"gl/inquiry/gl_trial_balance.php?", 'SA_GLANALYTIC_LIST', MENU_INQUIRY
		);

		$this->add_rapp_function(1, _("Balance &Sheet Drilldown"),
			"gl/inquiry/balance_sheet.php?", 'SA_GLANALYTIC_LIST', MENU_INQUIRY
		);

		$this->add_rapp_function(1, _("&Profit and Loss Drilldown"),
			"gl/inquiry/profit_loss.php?", 'SA_GLANALYTIC_LIST', MENU_INQUIRY
		);
		//
		
		$this->add_rapp_function(1, _("Banking &Reports"),
			"reporting/reports_main.php?Class=5", 'SA_BANKREP', MENU_REPORT
		);

		$this->add_rapp_function(1, _("General Ledger &Reports"),
			"reporting/reports_main.php?Class=6", 'SA_GLREP', MENU_REPORT);

		$this->add_module(_("Maintenance"));
		$this->add_lapp_function(2, _("Bank &Accounts"),
			"gl/manage/bank_accounts.php?", 'SA_BANKACCOUNT', MENU_MAINTENANCE);
		$this->add_lapp_function(2, _("&Quick Entries"),
			"gl/manage/gl_quick_entries.php?", 'SA_QUICKENTRY', MENU_MAINTENANCE);
		$this->add_lapp_function(2, _("Account &Tags"),
			"admin/tags.php?type=account", 'SA_GLACCOUNTTAGS', MENU_MAINTENANCE);
		$this->add_lapp_function(2, "","");
		$this->add_lapp_function(2, _("&Currencies"),
			"gl/manage/currencies.php?", 'SA_CURRENCY', MENU_MAINTENANCE);
		$this->add_lapp_function(2, _("&Exchange Rates"),
			"gl/manage/exchange_rates.php?", 'SA_EXCHANGERATE', MENU_MAINTENANCE);

		$this->add_rapp_function(2, _("&GL Accounts"),
			"gl/manage/gl_accounts.php?", 'SA_GLACCOUNT', MENU_ENTRY);
		$this->add_rapp_function(2, _("GL Account &Groups"),
			"gl/manage/gl_account_types.php?", 'SA_GLACCOUNTGROUP', MENU_MAINTENANCE);
		$this->add_rapp_function(2, _("GL Account &Classes"),
			"gl/manage/gl_account_classes.php?", 'SA_GLACCOUNTCLASS', MENU_MAINTENANCE);
		$this->add_rapp_function(2, _("&Closing GL Transactions"),
			"gl/manage/close_period.php?", 'SA_GLSETUP', MENU_MAINTENANCE);
		$this->add_rapp_function(2, _("&Revaluation of Currency Accounts"),
			"gl/manage/revaluate_currencies.php?", 'SA_EXCHANGERATE', MENU_MAINTENANCE);

		$this->add_extensions();
	}
}


