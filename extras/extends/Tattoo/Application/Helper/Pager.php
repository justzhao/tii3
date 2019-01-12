<?php
/**
 * 分页助手
 *
 * @author Alacner Zhang <alacner@gmail.com>
 * @version $Id: Pager.php 488 2014-10-14 10:03:34Z alacner $
 */

final class Tattoo_Application_Helper_Pager extends Desire_Application_Helper_Pager_Abstract
{
	public function render()
	{
		$multipage = '';
		if (array_key_exists('offset', $this->options)) {
			$multipage .= $this->options['curpage'] - $this->options['offset'] > 1 && $this->options['pages'] > $this->options['page'] ? sprintf('<a href="%s1%s">第一页</a>', $this->options['mpurl'], $this->options['ext']) : '<a href="javascript:void()">第一页</a>';
			$multipage .= $this->options['curpage'] > 1 ? sprintf('<a href="%s%d%s" class="up">上一页</a>', $this->options['mpurl'], ($this->options['curpage'] - 1), $this->options['ext']) : '<a class="up" href="javascript:void()">上一页</a>';
			for ($i = $this->options['from']; $i <= $this->options['to']; $i++) {
				$multipage .= $i == $this->options['curpage'] ? sprintf('<a class="cur" href="javascript:void()">%d</a>', $i) : sprintf('<a href="%s%d%s">%d</a>', $this->options['mpurl'], $i, $this->options['ext'], $i);
			}
			$multipage .= $this->options['curpage'] < $this->options['pages'] ? sprintf('<a href="%s%d%s" class="next">下一页</a>', $this->options['mpurl'], ($this->options['curpage'] + 1), $this->options['ext']) : '<span class="disabled">下一页</span>';
			//$multipage .= $this->options['to'] < $this->options['pages'] ? sprintf('<a href="%s%d%s">尾页</a>', $this->options['mpurl'], $this->options['pages'], $this->options['ext']) : '<span class="disabled">尾页</span>';
			//$multipage .= $this->options['pages'] > $this->options['page'] ? '<kbd><input type="text" name="custompage" size="3" onkeydown="if(event.keyCode==13) {window.location=\''.$this->options['mpurl'].'\'+this.value+\''.$this->options['ext'].'\'; return false;}" /></kbd>' : '';
		}
		return  '<div class="pagecon fn-right fn-clear"><div class="align">'.$multipage.'</div></div>';
	}
}