( function ( wp ) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InnerBlocks } = wp.blockEditor;
	const el = wp.element.createElement;

	registerBlockType( 'lpu/nav-group', {
		edit: function Edit() {
			const blockProps = useBlockProps();
			return el(
				'div',
				blockProps,
				el( InnerBlocks, {
					allowedBlocks: [ 'core/navigation-link', 'core/navigation-submenu' ],
					templateLock: false,
				} )
			);
		},
		save: function save() {
			return null;
		},
	} );
} )( window.wp );
