( function ( wp ) {
	'use strict';

	var blocks       = wp.blocks;
	var blockEditor  = wp.blockEditor;
	var components   = wp.components;
	var element      = wp.element;
	var i18n         = wp.i18n;
	var data         = wp.data;
	var el           = element.createElement;
	var Fragment     = element.Fragment;
	var __           = i18n.__;
	var registerBlockType = blocks.registerBlockType;
	var createBlock  = blocks.createBlock;
	var InnerBlocks  = blockEditor.InnerBlocks;
	var InspectorControls = blockEditor.InspectorControls;
	var MediaUpload  = blockEditor.MediaUpload;
	var MediaUploadCheck = blockEditor.MediaUploadCheck;
	var useBlockProps = blockEditor.useBlockProps;
	var useSelect     = data.useSelect;
	var useDispatch   = data.useDispatch;
	var Button        = components.Button;
	var PanelBody     = components.PanelBody;
	var SelectControl = components.SelectControl;
	var ToggleControl = components.ToggleControl;

	var frameOptions = [
		{ label: __( 'Aucun cadre', 'lpu-split-section' ), value: 'none' },
		{ label: __( 'Cadre écru', 'lpu-split-section' ), value: 'ecru' },
		{ label: __( 'Cadre vert foncé', 'lpu-split-section' ), value: 'green' },
		{ label: __( 'Cadre jaune', 'lpu-split-section' ), value: 'yellow' },
		{ label: __( 'Motif carré 1', 'lpu-split-section' ), value: 'motif-1' },
		{ label: __( 'Motif carré 2', 'lpu-split-section' ), value: 'motif-2' },
		{ label: __( 'Motif carré 3', 'lpu-split-section' ), value: 'motif-3' },
		{ label: __( 'Motif carré 4', 'lpu-split-section' ), value: 'motif-4' },
		{ label: __( 'Motif carré 5', 'lpu-split-section' ), value: 'motif-5' },
		{ label: __( 'Motif carré 7', 'lpu-split-section' ), value: 'motif-7' },
		{ label: __( 'Motif carré 8', 'lpu-split-section' ), value: 'motif-8' },
	];

	var allowedFrames = frameOptions.map( function ( option ) {
		return option.value;
	} );

	var parentTemplate = [
		[ 'lpu/split-zone', { side: 'left', frame: 'ecru', mediaFill: false } ],
		[ 'lpu/split-zone', { side: 'right', frame: 'none', mediaFill: true } ],
	];

	function getSafeFrame( frame ) {
		return allowedFrames.indexOf( frame ) !== -1 ? frame : 'ecru';
	}

	function getSafeSide( side ) {
		return side === 'right' ? 'right' : 'left';
	}

	function getZoneClassName( attributes ) {
		var side  = getSafeSide( attributes.side );
		var frame = getSafeFrame( attributes.frame );
		var classes = [
			'lpu-split-v2__zone',
			'lpu-split-v2__zone--' + side,
			'lpu-split-v2__zone--frame-' + frame,
		];

		if ( attributes.mediaFill ) {
			classes.push( 'lpu-split-v2__zone--media-fill' );
		}

		return classes.join( ' ' );
	}

	function getSectionClassName( attributes ) {
		var classes = [ 'lpu-split-v2', 'alignfull' ];

		if ( attributes && attributes.align ) {
			classes.push( 'align' + attributes.align );
		}

		return classes.join( ' ' );
	}

	function ZoneEdit( props ) {
		var attributes = props.attributes;
		var clientId   = props.clientId;
		var setAttributes = props.setAttributes;
		var innerBlocks = useSelect( function ( select ) {
			return select( 'core/block-editor' ).getBlocks( clientId );
		}, [ clientId ] );
		var blockEditorDispatch = useDispatch( 'core/block-editor' );
		var blockProps = useBlockProps( {
			className: getZoneClassName( attributes ),
			'data-lpu-side': getSafeSide( attributes.side ),
		} );

		function selectMedia( media ) {
			if ( ! media || ! media.url ) {
				return;
			}

			var imageAttributes = {
				alt:             media.alt || '',
				linkDestination: 'none',
				sizeSlug:        'full',
				url:             media.url,
			};

			if ( media.id ) {
				imageAttributes.id = media.id;
			}

			var imageBlock = createBlock( 'core/image', imageAttributes );
			var nextBlocks = ( innerBlocks || [] ).slice();
			var imageIndex = nextBlocks.findIndex( function ( block ) {
				return block.name === 'core/image';
			} );

			if ( imageIndex === -1 ) {
				nextBlocks.push( imageBlock );
			} else {
				nextBlocks[ imageIndex ] = imageBlock;
			}

			blockEditorDispatch.replaceInnerBlocks( clientId, nextBlocks, false );
			setAttributes( {
				mediaId:  media.id || 0,
				mediaUrl: media.url,
			} );
		}

		return el(
			Fragment,
		{},
			el(
				InspectorControls,
				{},
				el(
					PanelBody,
					{
						title: __( 'Apparence de cette moitié', 'lpu-split-section' ),
						initialOpen: true,
					},
					el( SelectControl, {
						label: __( 'Cadre', 'lpu-split-section' ),
						value: getSafeFrame( attributes.frame ),
						options: frameOptions,
						onChange: function ( value ) {
							setAttributes( { frame: getSafeFrame( value ) } );
						},
					} ),
					el( ToggleControl, {
						label: __( 'Image pleine zone', 'lpu-split-section' ),
						checked: !! attributes.mediaFill,
						help: __( 'S’applique aux blocs Image placés directement dans cette moitié.', 'lpu-split-section' ),
						onChange: function ( value ) {
							setAttributes( { mediaFill: !! value } );
						},
					} ),
					el(
						MediaUploadCheck,
						{},
						el( MediaUpload, {
							allowedTypes: [ 'image' ],
							value: attributes.mediaId || 0,
							onSelect: selectMedia,
							render: function ( renderProps ) {
								return el(
									Button,
									{
										variant: 'secondary',
										onClick: renderProps.open,
									},
									attributes.mediaId
										? __( 'Remplacer l’image de cette moitié', 'lpu-split-section' )
										: __( 'Choisir l’image de cette moitié', 'lpu-split-section' )
								);
							},
						} )
					)
				)
			),
			el(
				'div',
				blockProps,
				el( InnerBlocks, {
					templateLock: false,
					placeholder: __( 'Ajoutez le contenu de cette moitié…', 'lpu-split-section' ),
				} )
			)
		);
	}

	function ZoneSave( props ) {
		var blockProps = useBlockProps.save( {
			className: getZoneClassName( props.attributes ),
		} );

		return el(
			'div',
			blockProps,
			el( InnerBlocks.Content )
		);
	}

	function SectionEdit( props ) {
		var blockProps = useBlockProps( {
			className: getSectionClassName( props.attributes ),
		} );

		return el(
			'div',
			blockProps,
			el( InnerBlocks, {
				allowedBlocks: [ 'lpu/split-zone' ],
				orientation: 'horizontal',
				template: parentTemplate,
				templateLock: 'all',
			} )
		);
	}

	function SectionSave( props ) {
		var blockProps = useBlockProps.save( {
			className: getSectionClassName( props.attributes ),
		} );

		return el(
			'div',
			blockProps,
			el( InnerBlocks.Content )
		);
	}

	registerBlockType( 'lpu/split-section', {
		apiVersion: 3,
		title: __( 'Section côte à côte', 'lpu-split-section' ),
		description: __( 'Deux moitiés exactement, avec du contenu indépendant de chaque côté.', 'lpu-split-section' ),
		icon: 'columns',
		supports: {
			align: [ 'wide', 'full' ],
			html: false,
		},
		edit: SectionEdit,
		save: SectionSave,
	} );

	registerBlockType( 'lpu/split-zone', {
		apiVersion: 3,
		title: __( 'Zone de section côte à côte', 'lpu-split-section' ),
		description: __( 'Une moitié indépendante avec son propre cadre et ses propres blocs.', 'lpu-split-section' ),
		icon: 'align-wide',
		attributes: {
			side: {
				type: 'string',
				default: 'left',
			},
			frame: {
				type: 'string',
				default: 'ecru',
			},
			mediaFill: {
				type: 'boolean',
				default: false,
			},
			mediaId: {
				type: 'number',
				default: 0,
			},
			mediaUrl: {
				type: 'string',
				default: '',
			},
		},
		edit: ZoneEdit,
		save: ZoneSave,
	} );
} )( window.wp );
