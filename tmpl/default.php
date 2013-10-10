<?php // no direct access
defined('_JEXEC') or die('Restricted access');

switch($mode) {
	case(12345678):
	/* you can write your own code here.
	 * The easiest way is to copy the code bellow and than just change it
	 * as you desire. Don't forget to change the number in CASE() to
	 * do anything sane and then link it to output mode param in
	 * mod_autgen_menu.xml.
	 */
	break;

	// the output mode in module parameters
	case(1):
	default:
		// SHOW ARTICLES AS LINKS
		if ($artfunction == 'links') {
			$last_cat = false;
			foreach($list as $article) {
				$article_link = '<a href="'.$article->url.'" title="'.$article->title.'">'
				              . '<span>'.$article->title.'</span></a>';
				
				// generating the category link. If you want to display the categories in classic format instead of the blog format, use the following line instead:
				// if ($catfunction == 'links') $category_link = JURI::root( true ).'/index.php?option=com_content&view=category&id='.$article->catslug;
				if ($catfunction == 'links') $category_link = JURI::root( true ).'/index.php?option=com_content&view=category&layout=blog&id='.$article->catslug;
				//generate output

				/*  ------   F I R S T   A R T I C L E   E V E R    ----  */
				if ($last_cat === false) {
					switch ($catfunction) {
						case 'links':
							echo '<ul class="menu'.$params->get('moduleclass_sfx').'">'
							   .  '<li>'
							   .   '<a class="category" href="'.$category_link.'" title="'.$article->category.'">'
							   .    $article->category
							   .   '</a>
							   .   <ul>
							   .    <li>'.$article_link.'</li>'
							   ;
							break;
						case 'separators':
							echo '<ul class="menu'.$params->get('moduleclass_sfx').'">'
							   .  '<li>'
							   .   '<span class="separator">'
							   .    $article->category
							   .   '</span>'
							   .   '<ul>'
							   .    '<li>'.$article_link.'</li>'
							   ;
							break;
						case 'hidden':
							echo '<ul class="menu'.$params->get('moduleclass_sfx').'">'
							   .  '<li>'
							   .   $article_link
							   .  '</li>'
							   ;
							break;
					}

				/*  ------   S A M E   C A T E G O R Y    ----  */
				} elseif ($last_cat == $article->catid) {
					echo '<li>'.$article_link.'</li>';

				/*  ------   N E W   C A T E G O R Y    ---- */
				} else {
					switch ($catfunction) {
						case 'links':
							echo  '</ul>'
							   . '</li>'
							   . '<li>'.
							   .  '<a class="category" href="'.$category_link.'" title="'.$article->category.'">'
							   .   $article->category
							   .  '</a>'
							   .  '<ul>'
							   .   '<li>'
							   .    $article_link
							   .   '</li>'
							   ;
							break;
						case 'separators':
							echo  '</ul>'
							   . '</li>'
							   . '<li>'
							   .  '<span class="separator">'
							   .   $article->category
							   .  '</span>'
							   .  '<ul>'
							   .   '<li>'
							   .    $article_link
							   .   '</li>'
							   ;
							break;
						case 'hidden':
							echo '<li>'.$article_link.'</li>';
							break;
					}
				}
				$last_cat = $article->catid;
			}
			// finishing the output
			if ($last_cat !== false) {
				switch ($catfunction) {
					case 'links':
					case 'separators':
						echo '</ul></li></ul>';
						break;
					case 'hidden':
						echo '</ul>';
						break;
				}
			}

		// HIDE ARTICLES, SHOW JUST CATEGORIES
		} elseif ($artfunction == 'hidden') {
			$cat_out = array();
			foreach($list as $cat) {
				// generating the category link. If you want to display the categories in classic format instead of the blog format, use the following line instead:
				// $category_link = JURI::root( true ).'/index.php?option=com_content&view=category&id='.$cat->catslug;
				if ($catfunction == 'links') {
					$category_link = JURI::root( true ).'/index.php?option=com_content&view=category&layout=blog&id='.$cat->catslug;
					$cat_out[] = '<li><a href="'.$category_link.'" title="'.$cat->category.'">'.$cat->category.'</a></li>';
				} else {
					$cat_out[] = '<li>'.$cat->category.'</li>';
				}
			}
			echo '<ul class="menu'.$params->get('moduleclass_sfx').'">'.implode("\n", $cat_out)."\n</ul>";
		}
	break;
}
