jQuery(document).ready(function(){

	// Update our graph upon page load
	wpggUpdateDashGraph();
	
	// Settings screen toggle
	jQuery('#wpgg_dashboard_settings_button').click(function(){
		if ( jQuery('#wpgg_dashboard_settings').is(':visible') ) {
			jQuery('#wpgg_dashboard_settings').slideUp();
		} else {
			jQuery('#wpgg_dashboard_settings').slideDown();
		}
	});
	
	// Save our options
	jQuery('#graphite_dash_submit').click(function(){
		wpggSaveDashOptions();
		jQuery('#graphite_dashboard_graph h3 span').html(jQuery('#wpgg_dash_title').val());
		jQuery('#wpgg_dashboard_settings').slideUp();
		wpggUpdateDashGraph();
	});
	
	// Refresh the graph
	jQuery('#wpgg_dashboard_refresh_button').click(function(){
		wpggUpdateDashGraph();
	});
	
	// Hide the settings
	jQuery('#graphite_dash_cancel').click(function(){
		jQuery('#wpgg_dashboard_settings').slideUp();
	});
	
});

// Send our new settings back to the server
function wpggSaveDashOptions() {
	var data = {
		action: 'wpggSaveDashOptions', 
		wpgg_dash_title: jQuery('#wpgg_dash_title').val(), 
		wpgg_dash_server: jQuery('#wpgg_dash_server').val(), 
		wpgg_dash_metrics: jQuery('#wpgg_dash_metrics').val(), 
		wpgg_dash_duration: jQuery('#wpgg_dash_duration').val(), 
		wpgg_dash_colorscheme: jQuery('#wpgg_dash_colorscheme').val()
	};
	jQuery.post(ajaxurl, data, function(response) {
		wpggUpdateDashGraph();
	});
}

// Update the dashboard graph image
function wpggUpdateDashGraph() {
	jQuery('#graph_loading_overlay').show();
	if ( '' == jQuery('#wpgg_dash_server').val() ) {
		jQuery('#dash_graph_img').hide();
		jQuery('#dash_graph_message').show();
		jQuery('#graph_loading_overlay').hide();
		return;
	}
	var urlBase = jQuery('#wpgg_dash_server').val();
	var urlMetrics = jQuery('#wpgg_dash_metrics').val();
	var urlDuration = jQuery('#wpgg_dash_duration').val();
	var urlColors = '';
	if ( 'Light' == jQuery('#wpgg_dash_colorscheme').val() )
		urlColors = '&bgcolor=white&fgcolor=black';
	if ( 'Aqua' == jQuery('#wpgg_dash_colorscheme').val() )
		urlColors = '&bgcolor=03aeff&fgcolor=0000aa';
	if ( 'Fire' == jQuery('#wpgg_dash_colorscheme').val() )
		urlColors = '&bgcolor=ffb84d&fgcolor=a32900';
	var urlDynamic = urlBase + '/render?target=' + urlMetrics + '&from=' + urlDuration + 'hours' + urlColors;
	var urlStatic = '&width=960&height=540&lineMode=connected&lineWidth=4&hideLegend=true&fontSize=18#' + new Date().getTime();
	var urlFinal = urlDynamic + urlStatic;
	console.log(urlFinal);
	jQuery('#dash_graph_img').load(function(){
		jQuery('#dash_graph_message').hide();
		jQuery('#dash_graph_img').show();
		jQuery('#graph_loading_overlay').hide();
	}).attr('src', urlFinal);
}