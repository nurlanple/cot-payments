<?php

/**
 * Payments module
 *
 * @package payments
 * @version 1.1.2
 * @author CMSWorks Team
 * @copyright Copyright (c) CMSWorks.ru
 * @license BSD
 */
defined('COT_CODE') or die('Wrong URL.');

list($usr['auth_read'], $usr['auth_write'], $usr['isadmin']) = cot_auth('payments', 'any', 'RWA');
cot_block($usr['auth_read']);

require_once cot_incfile('payments', 'module');

$t = new XTemplate(cot_tplfile('payments.billing', 'module'));

$pid = cot_import('pid', 'G', 'INT');

// Получаем информацию о заказе
if ($pinfo = cot_payments_payinfo($pid))
{
	// Блокируем доступ к несобственным платежкам
	cot_block($usr['id'] == $pinfo['pay_userid']);

	// Если счета пользователей	 включены, то проверяем баланс
	if ($cfg['payments']['balance_enabled'] && $pinfo['pay_area'] != 'balance')
	{
		$ubalance = cot_payments_getuserbalance($usr['id']);
		if ($ubalance >= $pinfo['pay_summ'])
		{
			if (cot_payments_updatestatus($pid, 'paid'))
			{
				cot_payments_updateuserbalance($usr['id'], -$pinfo['pay_summ'], $pid);
				cot_redirect(cot_url('index'));
			}
		}
		else
		{
			$rsumm = $pinfo['pay_summ'] - $ubalance;
			cot_redirect(cot_url('payments', 'm=balance&n=billing&rsumm=' . $rsumm . '&pid=' . $pid, '', true));
		}
	}

	// Выводим подключенные платежные системы
	if ($cot_billings)
	{
		if (count($cot_billings) == 1)
		{
			foreach ($cot_billings as $bill)
			{
				cot_redirect(cot_url('plug', 'e=' . $bill['plug'] . '&pid=' . $pid, '', true));
			}
		}
		else
		{
			foreach ($cot_billings as $bill)
			{
				$t->assign(array(
					'BILL_ROW_TITLE' => $bill['title'],
					'BILL_ROW_ICON' => $bill['icon'],
					'BILL_ROW_URL' => cot_url('plug', 'e=' . $bill['plug'] . '&pid=' . $pid),
				));
				$t->parse('MAIN.BILLINGS.BILL_ROW');
			}
			$t->parse('MAIN.BILLINGS');
		}
	}
	else
	{
		$t->parse('MAIN.EMPTYBILLINGS');
	}
}
else
{
	cot_die();
}

$t->parse('MAIN');
$module_body = $t->text('MAIN');
?>