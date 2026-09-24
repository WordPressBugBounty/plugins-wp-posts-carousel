( function ( blocks, element, components, blockEditor, serverSideRender, i18n, apiFetch ) {
	if ( ! blocks || ! element || ! components || ! blockEditor || ! serverSideRender ) {
		return;
	}

	const el = element.createElement;
	const ServerSideRender = serverSideRender;
	const { InspectorControls, useBlockProps } = blockEditor;
	const { Notice, PanelBody, Placeholder, SelectControl, Spinner } = components;
	const ComboboxControl = components.ComboboxControl || components.__experimentalComboboxControl;
	const CarouselPickerControl = ComboboxControl || SelectControl;
	const __ = i18n.__;
	const sprintf = i18n.sprintf || function ( format, value ) {
		return String( format ).replace( '%d', value );
	};
	const { useEffect, useMemo, useRef, useState } = element;

	function carouselOption( carousel ) {
		const id = Number( carousel.ID || carousel.id || 0 );
		const title = carousel.post_title || carousel.title || '';

		if ( ! id ) {
			return null;
		}

		return {
			label: title || sprintf( __( 'Carousel #%d', 'wp-posts-carousel' ), id ),
			value: String( id ),
		};
	}

	blocks.registerBlockType( 'wp-posts-carousel/carousel', {
		apiVersion: 2,
		title: __( 'WP Posts Carousel', 'wp-posts-carousel' ),
		description: __( 'Embed a saved WP Posts Carousel.', 'wp-posts-carousel' ),
		category: 'widgets',
		icon: 'images-alt2',
		attributes: {
			carouselId: {
				type: 'number',
				default: 0,
			},
			id: {
				type: 'number',
				default: 0,
			},
			renderer: {
				type: 'string',
				default: '',
			},
		},
		supports: {
			align: [ 'wide', 'full' ],
			html: false,
		},
		edit( props ) {
			const attributes = props.attributes || {};
			const carouselId = Number( attributes.carouselId || attributes.id || 0 );
			const [ carousels, setCarousels ] = useState( [] );
			const [ loading, setLoading ] = useState( true );
			const [ error, setError ] = useState( '' );
			const previewRef = useRef( null );
			const setCarouselId = function ( value ) {
				props.setAttributes( {
					carouselId: Math.max( 0, Number( value || 0 ) ),
					id: Math.max( 0, Number( value || 0 ) ),
				} );
			};
			const options = useMemo( function () {
				const next = [
					{
						label: __( 'Select carousel', 'wp-posts-carousel' ),
						value: '0',
					},
					...carousels,
				];

				if (
					carouselId &&
					! next.some( function ( option ) {
						return option.value === String( carouselId );
					} )
				) {
					next.push( {
						label: sprintf( __( 'Current carousel #%d', 'wp-posts-carousel' ), carouselId ),
						value: String( carouselId ),
					} );
				}

				return next;
			}, [ carouselId, carousels ] );

			useEffect( function () {
				if ( ! apiFetch ) {
					setLoading( false );
					setError( __( 'Carousel list is unavailable in this editor.', 'wp-posts-carousel' ) );
					return;
				}

				let active = true;

				apiFetch( {
					path: '/wp-posts-carousel/v1/admin/carousels',
				} )
					.then( function ( result ) {
						if ( ! active ) {
							return;
						}

						setCarousels(
							( Array.isArray( result ) ? result : [] )
								.map( carouselOption )
								.filter( Boolean )
								.sort( function ( first, second ) {
									return first.label.localeCompare( second.label );
								} )
						);
						setError( '' );
					} )
					.catch( function () {
						if ( active ) {
							setError( __( 'Could not load saved carousels. Check permissions and plugin access.', 'wp-posts-carousel' ) );
						}
					} )
					.finally( function () {
						if ( active ) {
							setLoading( false );
						}
					} );

				return function () {
					active = false;
				};
			}, [] );

			useEffect( function () {
				if ( ! carouselId ) {
					return undefined;
				}

				let disposed = false;
				let observer = null;
				const timers = [];
				const loadPreviewCarousels = function () {
					const loader = window.wpPostsCarouselLazyLoad;
					const preview = previewRef.current;

					if ( disposed || ! loader || ! preview || typeof loader.load !== 'function' ) {
						return;
					}

					preview
						.querySelectorAll( '.cci-wpc-carousel[data-wpc-api]' )
						.forEach( function ( carousel ) {
							const state = carousel.dataset.wpcLazyState || '';
							const isFullReact = carousel.dataset.wpcRenderer === 'react' &&
								[ 'full', 'render' ].includes( carousel.dataset.wpcReactMode || '' );
							const isPhpPreview = carousel.dataset.wpcRenderer !== 'react';
							const isUninitializedOwl = carousel.classList.contains( 'owl-carousel' ) &&
								! carousel.classList.contains( 'owl-loaded' );
							const editorOwlInit = carousel.dataset.wpcEditorOwlInit || '';
							const editorOwlAttempts = Number( carousel.dataset.wpcEditorOwlAttempts || 0 );
							const hasReactRoot = Boolean(
								carousel.previousElementSibling &&
								carousel.previousElementSibling.getAttribute( 'data-wpc-react-root' ) === 'true' &&
								carousel.previousElementSibling.childElementCount > 0
							);
							const needsPhpInit = isPhpPreview &&
								isUninitializedOwl &&
								! [ 'loading', 'done' ].includes( editorOwlInit ) &&
								editorOwlAttempts < 4;

							if (
								state === 'loading' ||
								( state === 'ready' && ! needsPhpInit && ( ! isFullReact || hasReactRoot ) )
							) {
								return;
							}

							if ( needsPhpInit ) {
								carousel.dataset.wpcEditorOwlInit = 'loading';
								carousel.dataset.wpcEditorOwlAttempts = String( editorOwlAttempts + 1 );
							}

							const result = loader.load( carousel, {
								force: ( state === 'ready' && isFullReact && ! hasReactRoot ) || needsPhpInit,
								replaceHtml: needsPhpInit,
							} );

							if ( result && typeof result.catch === 'function' ) {
								result
									.then( function ( loadedCarousel ) {
										const initializedCarousel = loadedCarousel && loadedCarousel.dataset
											? loadedCarousel
											: carousel;

										if ( needsPhpInit ) {
											if ( initializedCarousel.classList.contains( 'owl-loaded' ) ) {
												initializedCarousel.dataset.wpcEditorOwlInit = 'done';
												return;
											}

											initializedCarousel.dataset.wpcEditorOwlInit = editorOwlAttempts >= 3
												? 'error'
												: '';
											if ( editorOwlAttempts < 3 && ! disposed ) {
												timers.push( window.setTimeout( loadPreviewCarousels, 300 ) );
											}
										}
									} )
									.catch( function () {
										if ( needsPhpInit ) {
											carousel.dataset.wpcEditorOwlInit = editorOwlAttempts >= 3
												? 'error'
												: '';
											if ( editorOwlAttempts < 3 && ! disposed ) {
												timers.push( window.setTimeout( loadPreviewCarousels, 450 ) );
											}
										}
									} );
							}
						} );
				};

				if ( previewRef.current && 'MutationObserver' in window ) {
					observer = new MutationObserver( loadPreviewCarousels );
					observer.observe( previewRef.current, {
						childList: true,
						subtree: true,
					} );
				}

				[ 0, 120, 450, 1000, 1800, 3200 ].forEach( function ( delay ) {
					timers.push( window.setTimeout( loadPreviewCarousels, delay ) );
				} );

				return function () {
					disposed = true;
					if ( observer ) {
						observer.disconnect();
					}
					timers.forEach( function ( timer ) {
						window.clearTimeout( timer );
					} );
				};
			}, [ carouselId ] );

			const renderCarouselSelect = function ( context ) {
				return el( CarouselPickerControl, {
					label: __( 'Carousel', 'wp-posts-carousel' ),
					value: String( carouselId || 0 ),
					options,
					disabled: loading,
					help: context === 'inspector' ? __( 'Choose one of the saved carousel definitions.', 'wp-posts-carousel' ) : undefined,
					placeholder: __( 'Search carousels', 'wp-posts-carousel' ),
					showSuggestionsWhenValueIsEmpty: true,
					onChange: setCarouselId,
				} );
			};
			const renderLoadingState = function () {
				return loading
					? el( 'div', { className: 'cci-wpc-block-loading' }, el( Spinner ) )
					: null;
			};
			const renderErrorState = function () {
				return error
					? el(
					Notice,
					{
						status: 'warning',
						isDismissible: false,
					},
					error
				)
					: null;
			};
			const selectCurrentBlock = function () {
				if (
					props.clientId &&
					window.wp &&
					window.wp.data &&
					typeof window.wp.data.dispatch === 'function'
				) {
					window.wp.data.dispatch( 'core/block-editor' ).selectBlock( props.clientId );
				}
			};
			const blockProps = useBlockProps
				? useBlockProps( {
					className: 'cci-wpc-block-editor',
				} )
				: {
					className: 'cci-wpc-block-editor',
				};
			const blockContent = carouselId
				? el(
					'div',
					{
						className: 'cci-wpc-block-editor-preview',
						onMouseDown: selectCurrentBlock,
						ref: previewRef,
					},
					el( ServerSideRender, {
						block: 'wp-posts-carousel/carousel',
						attributes,
					} )
				)
				: el(
					Placeholder,
					{
						icon: 'images-alt2',
						label: __( 'WP Posts Carousel', 'wp-posts-carousel' ),
					},
					renderLoadingState(),
					renderErrorState(),
					renderCarouselSelect( 'canvas' )
				);

			return el(
				element.Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{
							title: __( 'Carousel', 'wp-posts-carousel' ),
							initialOpen: true,
						},
						renderLoadingState(),
						renderErrorState(),
						renderCarouselSelect( 'inspector' )
					)
				),
				el( 'div', blockProps, blockContent )
			);
		},
		save() {
			return null;
		},
	} );
} )(
	window.wp && window.wp.blocks,
	window.wp && window.wp.element,
	window.wp && window.wp.components,
	window.wp && window.wp.blockEditor,
	window.wp && window.wp.serverSideRender,
	window.wp && window.wp.i18n,
	window.wp && window.wp.apiFetch
);
