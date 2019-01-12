<?php
/**
 * 分页助手
 *
 */
class Blaze_Controller_Helper_Pager extends Desire_Controller_Helper_Pager_Abstract
{
	public function render()
	{
		$multipage = '';
		if (array_key_exists('offset', $this->options)) {
			$multipage .= $this->options['curpage'] - $this->options['offset'] > 1 && $this->options['pages'] > $this->options['page'] ? sprintf('<a href="%s1%s">第一页</a>', $this->options['mpurl'], $this->options['ext']) : '';
			$multipage .= '&nbsp;';
			$multipage .= $this->options['curpage'] > 1 ? sprintf('<a href="%s%d%s" class="up">&lt;上一页</a>', $this->options['mpurl'], ($this->options['curpage'] - 1), $this->options['ext']) : '';
			$multipage .= '&nbsp;';
			for ($i = $this->options['from']; $i <= $this->options['to']; $i++) {
				$multipage .= $i == $this->options['curpage'] ? sprintf('<strong>%d</strong>', $i) : sprintf('<a href="%s%d%s">%d</a>', $this->options['mpurl'], $i, $this->options['ext'], $i);
				$multipage .= '&nbsp;';
			}
			$multipage .= $this->options['curpage'] < $this->options['pages'] ? sprintf('<a href="%s%d%s" class="next">下一页&gt;</a>', $this->options['mpurl'], ($this->options['curpage'] + 1), $this->options['ext']) : '';
			//$multipage .= $this->options['to'] < $this->options['pages'] ? sprintf('<a href="%s%d%s">尾页</a>', $this->options['mpurl'], $this->options['pages'], $this->options['ext']) : '<span class="disabled">尾页</span>';
			//$multipage .= $this->options['pages'] > $this->options['page'] ? '<kbd><input type="text" name="custompage" size="3" onkeydown="if(event.keyCode==13) {window.location=\''.$this->options['mpurl'].'\'+this.value+\''.$this->options['ext'].'\'; return false;}" /></kbd>' : '';
		}
		return $multipage;
	}
}