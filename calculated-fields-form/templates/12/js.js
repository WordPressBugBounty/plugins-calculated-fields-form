fbuilderjQuery(function(){
	var $ = fbuilderjQuery;

	$( '.cp_cff_12 #fbuilder .pbNext' )
	.add( '.cp_cff_12 #fbuilder .pbPrevious' )
	.add( '.cp_cff_12 #fbuilder .pbSubmit' )
	.add( '.cp_cff_12 #fbuilder input[type=submit]' )
	.add( '.cp_cff_12 #fbuilder input[type=reset]' )
	.add( '.cp_cff_12 #fbuilder input[type=button]' )
	.addClass( 'bttn-unite bttn-md bttn-primary' );

    $('.cp_cff_12').on( 'cff-couponbutton-added', function() {
        $( this ).find( '.apply-coupon' ).addClass( 'bttn-unite bttn-md bttn-primary' );
    });

	$('.cp_cff_12').on('change', '.cff-repeater-field [type="hidden"]', function() {
		$('.cp_cff_12 .cff-repeater-field .cff-repeater-remove-row').addClass('bttn-unite bttn-md bttn-primary');
	});
});