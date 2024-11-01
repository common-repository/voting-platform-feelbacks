<?php
function plugin_settings_page() {?>
	<div class="wrap">
		<h2>VICOMI PLUGIN SETTINGS</h2>
		<p>
			Want to show Vicomi Feelbacks in a specific area in your page?<br/>
			Simply copy and paste the following short code into page editor. <code>[vicomi_feelbacks]</code>
		</p>
		<p>Select where you want to show the Vicomi Feelbacks.</p>
		<?php
		if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ){
			 admin_notice();
		} ?>
		<form method="POST" action="options.php">
			<?php
				settings_fields( 'vicomi_cpt_fields' );
				do_settings_sections( 'vicomi_cpt_fields' );
				submit_button();
			?>
		</form>
	</div> <?php
}
	
	 function admin_notice() { ?>
        <div class="notice notice-success is-dismissible">
            <p>Your Vicomi settings have been updated! :)</p>
        </div><?php
    }
	
	function setup_sections() {
        add_settings_section( 'our_first_section', 'Custom your Vicomi', 'section_callback', 'vicomi_cpt_fields' );
    }
	
	 function section_callback( $arguments ) {
    	switch( $arguments['id'] ){
    		case 'our_first_section':
    			echo 'Show Vicomi on the following pages:';
    			break;
    	}
    }
	
	 function setup_fields() {
		$this_wp_fields = array();
				$args = array(
		   'public'   => true
		);
		$output = 'names'; // names or objects, note names is the default
		$post_types = get_post_types( $args, $output ); 
		
		$this_wp_fields["none"] = "none";
		$this_wp_fields["front_page"] = "front_page";
		$this_wp_fields["archive"] = "archive";
		foreach ( $post_types  as $post_type ) {
		   $this_wp_fields[$post_type] = $post_type;
		}  
		
		if(in_array('attachment',$this_wp_fields)){
			unset($this_wp_fields['attachment']); /*remove attachment as a check box option*/
		}
		if(in_array('archive',$this_wp_fields)){
			unset($this_wp_fields['archive']); /*remove archive as a check box option*/
		}
		if(in_array('product',$this_wp_fields)){
			unset($this_wp_fields['product']); /*remove archive as a check box option*/
		}
				
		$default_val_array = array();
			
		$fields = array(
        	array(
        		'uid' => 'vicomi_checkboxes',
        		'label' => 'Show in:',
        		'section' => 'our_first_section',
        		'type' => 'checkbox',
        		'options' => $this_wp_fields,
                'default' => $default_val_array
        	),array(
        		'uid' => 'vicomi_exclude_pages_id',
        		'label' => 'Exclude from the following pages:',
        		'section' => 'our_first_section',
        		'type' => 'text',
        		'placeholder' => 'e.g. 2,556,123,1308',
        		'helper' => 'Write page IDs separated by Comma e.g. 2,556,123,1308',
				'default' => ''
        	)
        );
				
    	foreach( $fields as $field ){
        	add_settings_field( $field['uid'], $field['label'], 'field_callback' , 'vicomi_cpt_fields', $field['section'], $field );
            register_setting( 'vicomi_cpt_fields', $field['uid'] );
    	}
    }

	  function field_callback( $arguments ) {
        $value = get_option( $arguments['uid'] );

        if( ! $value ) {
            $value = $arguments['default'];
        }
        switch( $arguments['type'] ){          
            case 'checkbox':
                if( ! empty ( $arguments['options'] ) && is_array( $arguments['options'] ) ){
					$options_markup = '';
					
					/*
					 check if any chk is checked, if not, set page and post as default
					*/
					if( !isset($value) || (count($value) <= 0) ){
						$value = array('page','post'); 
					}
					
					$iterator = 0;
					foreach( $arguments['options'] as $key => $label ){
                        $iterator++;
					    $options_markup .= sprintf( '<label for="%1$s_%6$s"><input id="%1$s_%6$s" name="%1$s[]" type="%2$s" value="%3$s" %4$s /> %5$s</label><br/>', $arguments['uid'], $arguments['type'], $key, checked( $value[ array_search( $key, $value, true ) ], $key, false ), $label, $iterator );
					}
                    printf( '<fieldset>%s</fieldset>', $options_markup );
                }
                break;
			case 'text':
				/* check if any info exists */
				if( (!isset($value)) || (empty($value)) ){ 
					$value = ''; 
				}
				$options_markup = '';
				$options_markup .= sprintf( '<input id="%1$s" name="%1$s" type="%2$s" value="%3$s" placeholder="%4$s"/><br/>', $arguments['uid'], $arguments['type'], $value ,$arguments['placeholder']);
				printf( '<fieldset>%s</fieldset>', $options_markup );
				break;
			
        }
        if( (array_key_exists ('helper',$arguments)) && ($helper = $arguments['helper']) ){
            printf( '<span class="helper"> %s</span>', $helper );
        }
        if( (array_key_exists ('supplimental',$arguments)) &&($supplimental = $arguments['supplimental']) ){
            printf( '<p class="description">%s</p>', $supplimental );
        }
    }
	
	 

?>