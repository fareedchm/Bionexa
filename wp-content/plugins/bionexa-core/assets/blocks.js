/* Editor block: "Bionexa Quote Form". No build step needed – uses WordPress globals. */
( function ( wp ) {
	var el = wp.element.createElement;
	var ServerSideRender = wp.serverSideRender;
	var useBlockProps = wp.blockEditor.useBlockProps;

	wp.blocks.registerBlockType( 'bionexa/quote-form', {
		apiVersion: 3,
		title: 'Bionexa Quote Form',
		description: 'Request-a-quote form. Change its list of services in Bionexa Settings.',
		category: 'widgets',
		icon: 'clipboard',
		keywords: [ 'quote', 'contact', 'form', 'enquiry' ],
		supports: { html: false, multiple: false },
		edit: function () {
			return el(
				'div',
				useBlockProps(),
				el( 'div', { style: { pointerEvents: 'none' } }, el( ServerSideRender, { block: 'bionexa/quote-form' } ) )
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
