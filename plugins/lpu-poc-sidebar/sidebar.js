(function (wp) {
	if (window.pagenow !== 'site-editor') {
		return;
	}

	var el = wp.element.createElement;
	var registerPlugin = wp.plugins.registerPlugin;
	var PluginSidebar = wp.editor.PluginSidebar;
	var PluginSidebarMoreMenuItem = wp.editor.PluginSidebarMoreMenuItem;
	var PanelBody = wp.components.PanelBody;

	registerPlugin('lpu-poc-sidebar', {
		icon: 'smiley',
		render: function () {
			return el(
				wp.element.Fragment,
				{},
				el(PluginSidebarMoreMenuItem, { target: 'lpu-poc-sidebar' }, 'LPU PoC'),
				el(
					PluginSidebar,
					{ name: 'lpu-poc-sidebar', title: 'LPU PoC' },
					el(
						PanelBody,
						{ title: 'Proof of concept' },
						el('p', {}, 'Si ce panneau est visible dans l’éditeur de site, l’option B fonctionne.')
					)
				)
			);
		},
	});
})(window.wp);
