<?php
/**
 * Creates the emails page tab when editing quizzes.
 *
 * @package QSM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads admin scripts and style
 *
 * @since 7.3.5
 */
function qsm_admin_enqueue_scripts_options_page_email($hook){
	if ( 'admin_page_mlw_quiz_options' != $hook ) {
		return;
	}
	if ( isset($_GET['tab'] ) && "emails" === $_GET['tab'] ){
		global $mlwQuizMasterNext;
		wp_enqueue_script( 'math_jax', QSM_PLUGIN_JS_URL.'/mathjax/tex-mml-chtml.js', false , '3.2.0' , true );		wp_enqueue_script( 'qsm_emails_admin_script', QSM_PLUGIN_JS_URL.'/qsm-admin-emails.js', array( 'jquery-ui-sortable', 'qmn_admin_js' ), $mlwQuizMasterNext->version, true);
		wp_enqueue_editor();
		wp_enqueue_media();
	}

}
add_action( 'admin_enqueue_scripts', 'qsm_admin_enqueue_scripts_options_page_email');


/**
 * Creates the email tab in the Quiz Settings Page
 *
 * @return void
 * @since 6.1.0
 */
function qsm_settings_email_tab() {
	global $mlwQuizMasterNext;
	$mlwQuizMasterNext->pluginHelper->register_quiz_settings_tabs( __( 'Emails', 'quiz-master-next' ), 'qsm_options_emails_tab_content' );
}
add_action( 'plugins_loaded', 'qsm_settings_email_tab', 5 );

/**
 * Creates the email content that is displayed on the email tab.
 *
 * @return void
 * @since 4.4.0
 */
function qsm_options_emails_tab_content() {
	global $wpdb;
	global $mlwQuizMasterNext;
	$quiz_id     = intval( sanitize_text_field( $_GET['quiz_id'] ) );
		$user_id = get_current_user_id();
	$js_data     = array(
		'quizID'      => $quiz_id,
		'nonce'       => wp_create_nonce( 'wp_rest' ),
		'qsm_user_ve' => get_user_meta( $user_id, 'rich_editing', true ),
	);
	wp_localize_script( 'qsm_emails_admin_script', 'qsmEmailsObject', $js_data );

	$categories = array();
	$enabled    = get_option( 'qsm_multiple_category_enabled' );
	if ( $enabled && $enabled != 'cancelled' ) {
		$query = $wpdb->prepare( "SELECT name FROM {$wpdb->prefix}terms WHERE term_id IN ( SELECT DISTINCT term_id FROM {$wpdb->prefix}mlw_question_terms WHERE quiz_id = %d ) ORDER BY name ASC", $quiz_id );
	} else {
		$query = $wpdb->prepare( "SELECT DISTINCT category FROM {$wpdb->prefix}mlw_questions WHERE category <> '' AND quiz_id = %d", $quiz_id );
	}
	$categories = $wpdb->get_results( $query, ARRAY_N );
	?>

<!-- Emails Section -->
<section class="qsm-quiz-email-tab" style="margin-top: 15px;">
	<button class="save-emails button-primary"><?php esc_html_e( 'Save Emails', 'quiz-master-next' ); ?></button>
	<button class="add-new-email button"><?php esc_html_e( 'Add New Email', 'quiz-master-next' ); ?></button>
	<a style="float: right;" class="qsm-show-all-variable-text"
		href="#"><?php _e( 'Insert Template Variables', 'quiz-master-next' ); ?> <span
			class="dashicons dashicons-upload"></span></a>
	<a style="margin: 0 10px; float: right;" href="https://quizandsurveymaster.com/docs/v7/emails-tab/" target="_blank"
		rel="noopener"><?php _e( 'View Documentation', 'quiz-master-next' ); ?></a>
	<div id="qsm_emails">
		<div style="margin-bottom: 30px;margin-top: 35px;" class="qsm-spinner-loader"></div>
	</div>
	<button class="save-emails button-primary"><?php esc_html_e( 'Save Emails', 'quiz-master-next' ); ?></button>
	<button class="add-new-email button"><?php esc_html_e( 'Add New Email', 'quiz-master-next' ); ?></button>
	<div class="qsm-alerts" style="margin-top: 20px;">
		<?php
					$mlwQuizMasterNext->alertManager->showAlerts();
		?>
	</div>
</section>

<!-- Templates -->
<script type="text/template" id="tmpl-email">
	<div class="qsm-email">
			<header class="qsm-email-header">
				<div><button class="delete-email-button"><span class="dashicons dashicons-trash"></span></button></div>
			</header>
			<main class="qsm-email-content">
				<div class="email-when">
					<div class="email-content-header">
						<h4><?php esc_html_e( 'When...', 'quiz-master-next' ); ?></h4>
						<p><?php esc_html_e( 'Set conditions for when this email should be sent. Leave empty to set this as an email that is always sent.', 'quiz-master-next' ); ?></p>
					</div>
					<div class="email-when-conditions">
						<!-- Conditions go here. Review template below. -->
					</div>
					<button class="new-condition button"><?php esc_html_e( 'Add additional condition', 'quiz-master-next' ); ?></button>
				</div>
				<div class="email-show">
					<div class="email-content-header">
						<h4><?php esc_html_e( '...Send', 'quiz-master-next' ); ?></h4>
						<p><?php esc_html_e( 'Create the email that should be sent when the conditions are met.', 'quiz-master-next' ); ?></p>
					</div>
					<label><?php esc_html_e( 'Who to send the email to? Put %USER_EMAIL% to send to user', 'quiz-master-next' ); ?></label>
					<?php do_action( 'qsm_after_send_email_label' ); ?>
					<input type="email" class="to-email" value="{{ data.to }}">
					<label><?php esc_html_e( 'Email Subject', 'quiz-master-next' ); ?></label>
					<input type="text" class="subject" value="{{ data.subject }}">
					<label><?php esc_html_e( 'Email Content', 'quiz-master-next' ); ?></label>
					<textarea id="email-template-{{ data.id }}" class="email-template">{{{ data.content }}}</textarea>
					<label><input type="checkbox" class="reply-to" <# if ( "true" == data.replyTo || true == data.replyTo ) { #>checked<# } #>>Add user email as Reply-To</label>
				</div>
			</main>
		</div>
	</script>

<script type="text/template" id="tmpl-email-condition">
	<div class="email-condition">
			<button class="delete-condition-button"><span class="dashicons dashicons-trash"></span></button>
			<?php if ( ! empty( $categories ) ) { ?>
				<select class="email-condition-category">
					<option value="" <# if (data.category == '') { #>selected<# } #>><?php _e( 'Quiz', 'quiz-master-next' ); ?></option>
					<option value="" disabled><?php _e( '---Select Category---', 'quiz-master-next' ); ?></option>
					<?php foreach ( $categories as $cat ) { ?>
					<option value="<?php echo esc_attr($cat[0]); ?>" <# if (data.category == '<?php echo esc_attr($cat[0]); ?>') { #>selected<# } #>><?php echo esc_attr($cat[0]); ?></option>
					<?php } ?>
					<?php do_action( 'qsm_results_page_condition_criteria' ); ?>
				</select>
			<?php } ?>
			<select class="email-condition-criteria">
				<option value="points" <# if (data.criteria == 'points') { #>selected<# } #>><?php _e( 'Total points earned', 'quiz-master-next' ); ?></option>
				<option value="score" <# if (data.criteria == 'score') { #>selected<# } #>><?php _e( 'Correct score percentage', 'quiz-master-next' ); ?></option>
				<?php do_action( 'qsm_email_condition_criteria' ); ?>
			</select>
			<?php do_action( 'qsm_email_extra_condition_fields' ); ?>
			<select class="email-condition-operator">
				<option class="default_operator" value="equal" <# if (data.operator == 'equal') { #>selected<# } #>><?php _e( 'is equal to', 'quiz-master-next' ); ?></option>
				<option class="default_operator" value="not-equal" <# if (data.operator == 'not-equal') { #>selected<# } #>><?php _e( 'is not equal to', 'quiz-master-next' ); ?></option>
				<option class="default_operator" value="greater-equal" <# if (data.operator == 'greater-equal') { #>selected<# } #>><?php _e( 'is greater than or equal to', 'quiz-master-next' ); ?></option>
				<option class="default_operator" value="greater" <# if (data.operator == 'greater') { #>selected<# } #>><?php _e( 'is greater than', 'quiz-master-next' ); ?></option>
				<option class="default_operator" value="less-equal" <# if (data.operator == 'less-equal') { #>selected<# } #>><?php _e( 'is less than or equal to', 'quiz-master-next' ); ?></option>
				<option class="default_operator" value="less" <# if (data.operator == 'less') { #>selected<# } #>><?php _e( 'is less than', 'quiz-master-next' ); ?></option>
				<?php do_action( 'qsm_email_condition_operator' ); ?>
			</select>
			<input type="text" class="email-condition-value condition-default-value" value="{{ data.value }}">
			<?php do_action( 'qsm_email_condition_value' ); ?>
		</div>
	</script>
<!--Template popup-->
<div class="qsm-popup qsm-popup-slide" id="show-all-variable" aria-hidden="false">
	<div class="qsm-popup__overlay" tabindex="-1" data-micromodal-close="">
		<div class="qsm-popup__container" role="dialog" aria-modal="true" aria-labelledby="modal-3-title">
			<header class="qsm-popup__header" style="display: block;">
				<h2 class="qsm-popup__title"><?php _e( 'Template Variables', 'quiz-master-next' ); ?></h2>
				<span class="description">
					<?php _e( 'Use these dynamic variables to customize your quiz or survey. Just copy and paste one or more variables into the content templates and these will be replaced by actual values when user takes a quiz.', 'quiz-master-next' ); ?>
					<br /><b><?php _e( 'Note: ', 'quiz-master-next' ); ?></b>
					<?php _e( 'Always use uppercase while using these variables.', 'quiz-master-next' ); ?>
				</span>
			</header>
			<main class="qsm-popup__content" id="show-all-variable-content">
				<?php
						$variable_list                                = qsm_text_template_variable_list();
						$variable_list['Core']['%QUESTIONS_ANSWERS_EMAIL%'] = __( 'Shows the question, the answer provided by user, and the correct answer.', 'quiz-master-next' );
						unset( $variable_list['Core']['%FACEBOOK_SHARE%'] );
						unset( $variable_list['Core']['%TWITTER_SHARE%'] );
						//filter to add or remove variables from variable list for email tab
						$variable_list = apply_filters( 'qsm_text_variable_list_email', $variable_list );

						if ( $variable_list ) {
							foreach ( $variable_list as $category_name => $category_variables ) {
								?>
								<div><h2><?php echo esc_attr($category_name);?></h2></div>
								<?php
                foreach ($category_variables as $variable_key => $variable) {
                ?>
								<div class="popup-template-span-wrap">
									<span class="qsm-text-template-span">
										<span class="button button-default template-variable"><?php echo esc_attr($variable_key); ?></span>
										<span class="button click-to-copy">Click to Copy</span>
										<span class="temp-var-seperator">
											<span class="dashicons dashicons-editor-help qsm-tooltips-icon">
												<span class="qsm-tooltips"><?php echo esc_attr($variable); ?></span>
											</span>
										</span>
									</span>
								</div>
								<?php
                }
							}
						}
						?>
			</main>
			<footer class="qsm-popup__footer" style="text-align: right;">
				<button class="button button-default" data-micromodal-close=""
					aria-label="Close this dialog window"><?php _e( 'Close [Esc]', 'quiz-master-next' ); ?></button>
			</footer>
		</div>
	</div>
</div>
<?php
}
?>