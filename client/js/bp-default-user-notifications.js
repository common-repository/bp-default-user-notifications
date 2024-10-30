
"use strict";

(function( $ ) {

	$( document ).ready(function() {

		let button 	= $(".bp-d-u-submit"),
		reset	= $(".bp-d-u-reset"),
		warning = $(".bt-d-u-warning"),
		pInter	= null;

		button.on("click", function(ev, offset ){

			let checkboxs =[]
			,	data = {
					"action" : "bp_default_notifications",
					"security" : ajax_object.nonce,
					"posted"	: true,
					"offset"	: offset// set if if passed by trigger Method
			}

			$(".chk:input").each(function( index ) {

				let notification = {};

				notification[ $(this).attr("name") ] = ( $(this).prop('checked') === true ) ? "yes" : "no";
				checkboxs.push( notification );

			});

			if( ( checkboxs.length > 0 ) ){

				data.process = checkboxs;

			}

			// send request
			sendRequest( data );

		});

		reset.on("click", function( ev ){

			$(".chk:input").each(function( index ) {

				$(this).prop( "checked", true );

			});

			button.trigger("click");

		});

		function sendRequest( data ){

			let p	= $("<p>", { "text" : "Updating Notifications......"} );

			$.ajax({
				url: ajax_object.ajax_url,
				dataType: 'json',
				type: 'post',
				data : data,
				cache : false,
				beforeSend: function() {

					warning.prepend( p );

					clearInterval( pInter );

					pInter = setInterval(function(){

						p.toggle("slow");

					}, 2000 );

					button.prop( 'disabled', true );

					reset.prop( 'disabled', true );

				},
				success : function( res ){

					clearInterval( pInter );

					p.empty();

					// trigger it if not 0
					if( ( res.new_offset !== 0 ) ){

						button.trigger("click", [ res.new_offset ]  );

					}else{

						button.prop( 'disabled', false );

						reset.prop( 'disabled', false );

						if( res.success ){

							p.append( res.msg );

							setTimeout( function(){

								p.toggle("slow");
								p.remove();

							}, 5000 );

						}
						else{

							p.append( res.err );

						}

					}

					//console.log( res );

				},
				error : function( jqXhr, textStatus, errorThrown  ){

					p.empty();
					p.append( errorThrown );

				}

			});

		}

	});

})( jQuery );