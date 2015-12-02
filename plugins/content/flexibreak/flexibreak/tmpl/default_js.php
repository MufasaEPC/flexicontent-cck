<?php
/**
 * @version      $Revision: 1.0$
 * @package      Joomla
 * @license      GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 */
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

$custom_allpages = JText::_( $this->params->get('custom_allpages', 'FLEXIBREAK_ALL_PAGES') );
$display_method = $this->params->get('display_method', 1);
$onclick = $display_method == 1  ?  'javascript:return false;'  : '';  // need to disable anchor following
?>
<div class="contenttoc" id="articleTOC">
	<p class="tocHeader"><?php echo JText::_( 'FLEXIBREAK_TABLE_OF_CONTENT' ) ?></p>

	<ul class="tocList">
		
		<?php if ( $this->params->get('allpages_link', 1) && $display_method == 1 ) : ?>
			<li>
				<a class="tocAll" id="showall" onclick="<?php echo $onclick ?>" href="#showall"> - <?php echo $custom_allpages; ?> - </a>
			</li>
		<?php endif; ?>
		
		<?php
		$curr_link = JUri::getInstance()->toString(array('path', 'query'));
		$link_class = $display_method==1 ? ' tocPaginated' : ($display_method==0 ? ' tocScrolled' : ' tocReloaded');
		
		$n = !empty($this->texts[0]) ? -1 : 0;
		for ($i = 0; $i < $this->pagescount; $i++) :
			$page = $this->_generateToc($this->row, $i);
			if ($display_method == 1) $link = '#'.$page->id;
			else if ($display_method == 2) $link = $page->link;
			else  $link = $curr_link.'#'.$page->id.'_toc_page';
			$active = $this->limitstart == $i  ?  ' active'  : '';
			$n++;
		?>
			<li class="<?php echo $active ?>">
				<a class="tocLink<?php echo $link_class; ?>" id="<?php echo $page->id ?>_tocLink" href="<?php echo $link; ?>" onclick="<?php echo $onclick ?>" >
					<?php echo $n .". ". $page->title ?>
				</a>
			</li>
		<?php endfor; ?>
		
	</ul>

	<?php if ( $this->params->get('pagination', 1) == 1 ) : ?>
	<?php echo $this->loadTemplate('pagination_js'); ?>
	<?php endif; ?>
</div>