<?php
// make sure that SimplePie has been already loaded
require_once (ABSPATH . WPINC . '/class-feed.php');		//<-- very importat. Without this line, the cache setting breaks.

class AmazonAutoLinks_SimplePie_ extends SimplePie
{
	public $classver = 'standard';
	public $sortorder = 'date';
	public function set_sortorder($sortorder) {
		$this->sortorder = $sortorder;
	}
	
	/* overriding the default SimplePie method, get_items() of v 1.2.1 */
	public function get_items($start = 0, $end = 0)
	{

		global $wp_version;
		if ( version_compare( $wp_version , '3.5', "<" ) ) {
			
		
			if (!isset($this->data['items']))
			{
				if (!empty($this->multifeed_objects))
				{
					$this->data['items'] = SimplePie::merge_items($this->multifeed_objects, $start, $end, $this->item_limit);
				}
				else
				{
					$this->data['items'] = array();
					if ($items = $this->get_feed_tags(SIMPLEPIE_NAMESPACE_ATOM_10, 'entry'))
					{
						$keys = array_keys($items);
						foreach ($keys as $key)
						{
							$this->data['items'][] = new $this->item_class($this, $items[$key]);
						}
					}
					if ($items = $this->get_feed_tags(SIMPLEPIE_NAMESPACE_ATOM_03, 'entry'))
					{
						$keys = array_keys($items);
						foreach ($keys as $key)
						{
							$this->data['items'][] = new $this->item_class($this, $items[$key]);
						}
					}
					if ($items = $this->get_feed_tags(SIMPLEPIE_NAMESPACE_RSS_10, 'item'))
					{
						$keys = array_keys($items);
						foreach ($keys as $key)
						{
							$this->data['items'][] = new $this->item_class($this, $items[$key]);
						}
					}
					if ($items = $this->get_feed_tags(SIMPLEPIE_NAMESPACE_RSS_090, 'item'))
					{
						$keys = array_keys($items);
						foreach ($keys as $key)
						{
							$this->data['items'][] = new $this->item_class($this, $items[$key]);
						}
					}
					if ($items = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_RSS_20, 'item'))
					{
						$keys = array_keys($items);
						foreach ($keys as $key)
						{
							$this->data['items'][] = new $this->item_class($this, $items[$key]);
						}
					}
				}
			}

			if (!empty($this->data['items']))
			{
				// If we want to order it by date, check if all items have a date, and then sort it
				if ($this->order_by_date && empty($this->multifeed_objects))
				{
					if (!isset($this->data['ordered_items']))
					{
						$do_sort = true;
						foreach ($this->data['items'] as $item)
						{
							if (!$item->get_date('U'))
							{
								$do_sort = false;
								break;
							}
						}
						// $item = null;
						$this->data['ordered_items'] = $this->data['items'];
						if ($do_sort)
						{					
							if ($this->sortorder == 'date') {
								usort($this->data['ordered_items'], array(get_class($this), 'sort_items'));
							}
							else if ($this->sortorder == 'title') {
								usort($this->data['ordered_items'], array(get_class($this), 'sort_items_by_title'));
							}
							else  {
								usort($this->data['ordered_items'], array(get_class($this), 'sort_items_by_random'));
							}
						} else {
						
						}
					}
					$items = $this->data['ordered_items'];
				}
				else
				{
			
					// Sort 
					if ($this->sortorder == 'date') {
						usort($this->data['items'], array(get_class($this), 'sort_items'));
					}
					else if ($this->sortorder == 'title') {
						usort($this->data['items'], array(get_class($this), 'sort_items_by_title'));
					}
					else  {
						usort($this->data['items'], array(get_class($this), 'sort_items_by_random'));
					}
			
					$items = $this->data['items'];			
				}

				// Slice the data as desired
				if ($end === 0)
				{
					return array_slice($items, $start);
				}
				else
				{
					return array_slice($items, $start, $end);
				}
			}
			else
			{		
				return array();
			}
		}
		else if ( version_compare($wp_version, '3.5', ">=" ) ) {
			/*
			 * for Simple Pie v1.3, WordPress v3.5 above
			 * */	
			if (!isset($this->data['items']))
			{
				if (!empty($this->multifeed_objects))
				{
					$this->data['items'] = SimplePie::merge_items($this->multifeed_objects, $start, $end, $this->item_limit);
				}
				else
				{
					$this->data['items'] = array();
					if ($items = $this->get_feed_tags(SIMPLEPIE_NAMESPACE_ATOM_10, 'entry'))
					{
						$keys = array_keys($items);
						foreach ($keys as $key)
						{
							$this->data['items'][] = $this->registry->create('Item', array($this, $items[$key]));
						}
					}
					if ($items = $this->get_feed_tags(SIMPLEPIE_NAMESPACE_ATOM_03, 'entry'))
					{
						$keys = array_keys($items);
						foreach ($keys as $key)
						{
							$this->data['items'][] = $this->registry->create('Item', array($this, $items[$key]));
						}
					}
					if ($items = $this->get_feed_tags(SIMPLEPIE_NAMESPACE_RSS_10, 'item'))
					{
						$keys = array_keys($items);
						foreach ($keys as $key)
						{
							$this->data['items'][] = $this->registry->create('Item', array($this, $items[$key]));
						}
					}
					if ($items = $this->get_feed_tags(SIMPLEPIE_NAMESPACE_RSS_090, 'item'))
					{
						$keys = array_keys($items);
						foreach ($keys as $key)
						{
							$this->data['items'][] = $this->registry->create('Item', array($this, $items[$key]));
						}
					}
					if ($items = $this->get_channel_tags(SIMPLEPIE_NAMESPACE_RSS_20, 'item'))
					{
						$keys = array_keys($items);
						foreach ($keys as $key)
						{
							$this->data['items'][] = $this->registry->create('Item', array($this, $items[$key]));
						}
					}
				}
			}

			if (!empty($this->data['items']))
			{
				// If we want to order it by date, check if all items have a date, and then sort it
				if ($this->order_by_date && empty($this->multifeed_objects))
				{
					if (!isset($this->data['ordered_items']))
					{
						$do_sort = true;
						foreach ($this->data['items'] as $item)
						{
							if (!$item->get_date('U'))
							{
								$do_sort = false;
								break;
							}
						}
						$item = null;
						$this->data['ordered_items'] = $this->data['items'];
						if ($do_sort)
						{
							usort($this->data['ordered_items'], array(get_class($this), 'sort_items'));
						}
					}
					$items = $this->data['ordered_items'];
				}
				else
				{
					
					// Sort 
					if ($this->sortorder == 'date') {
						usort($this->data['items'], array(get_class($this), 'sort_items'));
					}
					else if ($this->sortorder == 'title') {
						usort($this->data['items'], array(get_class($this), 'sort_items_by_title'));
					}
					else  {
						usort($this->data['items'], array(get_class($this), 'sort_items_by_random'));
					}
					
					$items = $this->data['items'];
				}

				// Slice the data as desired
				if ($end === 0)
				{
					return array_slice($items, $start);
				}
				else
				{
					return array_slice($items, $start, $end);
				}
			}
			else
			{
				return array();
			}		
		}
	}
	
	public static function sort_items_by_random($a, $b)
	{
		return rand(-1, 1);
	}	
	public static function sort_items_by_title($a, $b)
	{
		$a_title = preg_replace('/#\d+?:\s?/i', '', $a->get_title());
		$b_title = preg_replace('/#\d+?:\s?/i', '', $b->get_title());
		return strcmp($a_title,$b_title);
	}
	
	function set_force_cache_class($class = 'SimplePie_Cache')
	{
		$this->cache_class = $class;
	}
	function set_force_file_class($class = 'SimplePie_File')
	{
		$this->file_class = $class;
	}	
}
?>