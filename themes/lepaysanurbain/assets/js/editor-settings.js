( function () {
	// there is no build step (at the moment), therefore use the wp objects
	// directly
	const { registerPlugin } = wp.plugins;
	const { PluginDocumentSettingPanel } = wp.editPost;
	const { ToggleControl } = wp.components;
	const { createElement } = wp.element;
	const { useSelect, useDispatch } = wp.data;

	function LpuHeaderSettings() {
		const postType = useSelect( ( select ) => select( 'core/editor' ).getCurrentPostType(), [] );
		const meta = useSelect( ( select ) => select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {}, [] );
		const { editPost } = useDispatch( 'core/editor' );

		if ( 'page' !== postType ) {
			return null;
		}

		const logoAvailable = Boolean( window.lpuEditorSettings?.logoAvailable );

		return createElement(
			PluginDocumentSettingPanel,
			{
				name: 'lpu-header-settings',
				title: 'Header de la page',
				className: 'lpu-header-settings',
			},
			createElement( ToggleControl, {
				label: 'Menu transparent sur cette page',
				help: logoAvailable
					? 'À utiliser uniquement si un hero contrasté se trouve directement derrière le header.'
					: 'La variante transparente est indisponible sur ce site.',
				checked: Boolean( meta.lpu_header_transparent ),
				disabled: ! logoAvailable,
				onChange: ( value ) => editPost( { meta: { lpu_header_transparent: value } } ),
			} )
		);
	}

	registerPlugin( 'lpu-header-settings', {
		render: LpuHeaderSettings,
	} );
}() );
