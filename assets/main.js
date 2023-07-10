jQuery( document ).ready(function($) {
	if( $('.wpg-list-search-form').length ) {
		// prevent form search from original plugin
		$('.wpg-list-search-form input').off('input'); 
		// add search including spellings
		$('.wpg-list-search-form input').on('input',function(e) {
			var $elem = $(this).closest('.wpg-list-wrapper');
			var $keyword = $(this).val().toLowerCase();
				
			$elem.find('.wpg-list-block').each(function() {
				var $elem_list_block = $(this);
				var $block_visible_items = 0;
				
				$elem_list_block.find('.wpg-list-item').each(function() {
					if( $(this).text().toLowerCase().match( $keyword ) ) {
						$(this).show();
						$block_visible_items++;
					} else {
						var $spellings = $(this).attr('data-spellings').split('|');
						if ($spellings) {
							var $foundSpelling = false;
							for (let i = 0; i < $spellings.length; i++) {
								if ($spellings[i].includes($keyword)) {
									$(this).show();
									$block_visible_items++;
									$foundSpelling = true;
								}
							}
							if (! $foundSpelling) {
								$(this).hide();
							}
						} else {
							$(this).hide();
						}
					}
				});
				
				var $filter_base = $elem_list_block.data('filter-base');
				var $filter_source = $elem.find('.wpg-list-filter a[data-filter=".wpg-filter-'+$filter_base+'"]');
				var $active_block = $elem.find('.wpg-list-filter a.mixitup-control-active').data('filter');
				
				if ( $block_visible_items > 0 ) {
					$elem_list_block.removeClass('wpg-removed');
					
					if ( $active_block != 'all' ) {
						if ( $elem_list_block.is( $elem.find( $active_block ) ) ) {
							$elem.find( $active_block ).show();
						}
					} else {
						$elem_list_block.show();
					}
					
					$filter_source.removeClass('filter-disable').addClass('filter');
				} else {
					$elem_list_block.addClass('wpg-removed');
					
					if ( $active_block != 'all' ) {
						if ( $elem_list_block.is( $elem.find( $active_block ) ) ) {
							$elem.find( $active_block ).hide();
						}
					} else {
						$elem_list_block.hide();
					}
					
					$filter_source.removeClass('filter').addClass('filter-disable');
				}
			});
			
			if( $keyword == '' ) {
				$elem.find('.wpg-list-block').show();
				$elem.find('.wpg-list-item').show();
			}
		});
		
		$('.wpg-list-search-form input').val('');
	}
});