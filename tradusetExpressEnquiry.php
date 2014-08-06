<?php
/**
 * Created by PhpStorm.
 * User: dirk
 * Date: 31.07.14
 * Time: 16:57
 */
/*

Plugin Name: Traduset Anfrage Widget

Description: Ein Webformular für die Sidebar

*/


// Creating the widget
class traduset_enquiry_widget extends WP_Widget
{

    function __construct()
    {
        parent::__construct(
// Base ID of your widget
            'traduset_enquiry_widget',

// Widget name will appear in UI
            __('Traduset Anfrage Widget', 'traduset_enquiryt_domain'),

// Widget description
            array('description' => __('Ein Expressanfrageformular für die Seitenleiste', 'wpb_widget_domain'),)
        );
    }

// Creating widget front-end
// This is where the action happens
    public function widget($args, $instance)
    {
        $title = apply_filters('widget_title', $instance['title']);
        $enquiryEmail = $instance['enquiryEmail'];
        $language = $instance['language'];

        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'] . '/enquiry/';

// before and after widget arguments are defined by themes
        echo $args['before_widget'];
        if (!empty($title))
            echo $args['before_title'] . $title . $args['after_title'];


// This is where you run the code and display the output
        $enquiryFormValues = array();
        $enquiryFormErrors = array();


//if form is submited check input
        if (isset ($_POST["submitExpressEnquiry"])) {
            $enquiryFormValues['sourceLanguage'] = $_POST['sourceLanguage'] = isset($_POST['sourceLanguage']) ? htmlentities($_POST['sourceLanguage']) : '';
            $enquiryFormValues['targetLanguage'] = $_POST['targetLanguage'] = isset($_POST['targetLanguage']) ? htmlentities($_POST['targetLanguage']) : '';
            $enquiryFormValues['customerName'] = $_POST['customerName'] = isset($_POST['customerName']) ? htmlentities($_POST['customerName']) : '';

            if (empty($_POST['sourceLanguage'])) {
                $missingSourceLanguage = __('Please fill the Source language field.', 'traduset');
                $enquiryFormErrors['sourceLanguage'] = "<div class=\"error\">" . $missingSourceLanguage . "</div>";
                $enquiryFormErrors['sourceLanguageErrorClass'] = ' class="error" ';
            }
            if (empty($_POST['targetLanguage'])) {
                $missingTargetLanguage = __('Please fill the Target language field.', 'traduset');
                $enquiryFormErrors['targetLanguage'] = "<div class=\"error\">" . $missingTargetLanguage . "</div>";
                $enquiryFormErrors['targetLanguageErrorClass'] = ' class="error" ';
            }
            if (empty($_POST['customerName'])) {
                $missingNameMessage = __('Please fill the Name field.', 'traduset');
                $enquiryFormErrors['customerName'] = "<div class=\"error\">" . $missingNameMessage . "</div>";
                $enquiryFormErrors['customerNameErrorClass'] = ' class="error" ';
            }

            if (isset($_POST["customerEmail"])) {
                if (!filter_var($_POST['customerEmail'], FILTER_VALIDATE_EMAIL)) {
                    $invalidEmailMessage = __('The E-Mail seems to be invalid.', 'traduset');
                    $enquiryFormErrors['customerEmail'] = "<div class=\"error\">" . $invalidEmailMessage . "</div>";
                    $enquiryFormErrors['customerEmailErrorClass'] = ' class="error" ';
                }
                $enquiryFormValues['customerEmail'] = isset($_POST['customerEmail']) ? htmlentities($_POST['customerEmail']) : '';
            }

            if (count($enquiryFormErrors) == 0) {


                $allowed = array('pdf', 'gif', 'jpg', 'JPG', 'png', 'docx', 'doc', 'xls', 'xlsx', 'ppt', 'pptx', 'ods', 'csv', 'txt', 'pages', 'rtf');
                $attachments = array();

                foreach ($_FILES['uploadfile']['tmp_name'] as $key => $file_tmp) {
                    //Get the temp file path


                    $file_name = $_FILES['uploadfile']['name'][$key];
                    $error = $_FILES['uploadfile']['error'][$key];

                    if (!$file_name)
                        break;

                    if ($error == 0) {
                        $ext = pathinfo($file_name, PATHINFO_EXTENSION);
                        if (!in_array($ext, $allowed)) {
                            $invalidFileFormatMessage = __('invalidFileFormatMessage', 'traduset');
                            $enquiryFormErrors['uploadFile'] = "<div class=\"error\">" . $invalidFileFormatMessage . "</div>";
                        } else {
                            //keine Fehler, file wird in upload dir beweget
                            if ($file_tmp != "") {
                                $new_file_path = $base_dir . $file_name;
                                if (move_uploaded_file($file_tmp, $new_file_path)) {
                                    array_push($attachments, $new_file_path);
                                } else {
                                    //file konnte nicht bewegt werden
                                    $uploadErrorMessage = __('uploadErrorMessage', 'traduset');
                                    $enquiryFormErrors['uploadFile'] = "<div class=\"error\">" . $uploadErrorMessage . "</div>";
                                }
                            }


                        }
                    } else {
                        if ($error == 1) {
                            $maxFileSizeMessage = __('maxFileSizeMessage');
                            $enquiryFormErrors['uploadFile'] = "<div class=\"error\">" . $maxFileSizeMessage . "</div>";
                        } elseif ($error > 1) {
                            $uploadErrorMessage = __('uploadErrorMessage', 'traduset');
                            $enquiryFormErrors['uploadFile'] = "<div class=\"error\">" . $uploadErrorMessage . "</div>";
                        }
                    }
                }
            }
            if (count($enquiryFormErrors) > 0) {
                $content = $this->getEnquiryForm($enquiryFormValues, $enquiryFormErrors);
            } else {

                $headers = 'From: Taduset Übersetzungsbüro <info@traduset.de>' . "\r\n";
                $subject = 'Traduset Expressanfrage von ' . $_POST['customerName'];

                $format = "Expressanfrage

Von: %s, %s
Ausgangssprache: %s
Zielsprache: %s

---
Diese E-Mail wurde über das Expressformular von traduset.de gesendet";

                $message = sprintf($format, $_POST['customerName'], $_POST['customerEmail'], $_POST['sourceLanguage'], $_POST['targetLanguage']);

                if (!filter_var($enquiryEmail, FILTER_VALIDATE_EMAIL))
                    $enquiryEmail = 'info@traduset.de';

                if (wp_mail($enquiryEmail, $subject, $message, $headers, $attachments)) {
                    $successMessage = __('successMessage', 'traduset');
                    $content = "<div class='success'>" . $successMessage . "</div>";

                    //files nach dem emailvesand wieder löschen
                    foreach ($attachments as $attachment) {
                        unlink($attachment);
                    }
                } else {
                    $sendErrorMessage = __('sendErrorMessage', 'traduset');
                    $content = "<div class=\"error\">" . $sendErrorMessage . "</div>";
                }

            }
        } else {
            $content = $this->getEnquiryForm($enquiryFormValues, $enquiryFormErrors);
        }

        echo __($content, 'traduset_enquiry_domain');


        echo $args['after_widget'];
    }

// Widget Backend
    public function form($instance)
    {
        if (isset($instance['title'])) {
            $title = $instance['title'];
        } else {
            $title = __('', 'traduset_enquiry_domain');
        }

        if (isset($instance['enquiryEmail'])) {
            $enquiryEmail = $instance['enquiryEmail'];
        }

        if (isset($instance['language'])) {
            $language = $instance['language'];
        }
// Widget admin form
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
                   name="<?php echo $this->get_field_name('title'); ?>" type="text"
                   value="<?php echo esc_attr($title); ?>"/>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('enquiryEmail'); ?>"><?php _e('Email:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('enquiryEmail'); ?>"
                   name="<?php echo $this->get_field_name('enquiryEmail'); ?>" type="text"
                   value="<?php echo esc_attr($enquiryEmail); ?>"/>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('language'); ?>">Sprache</label>

            <select class="widefat" id="<?php echo $this->get_field_id('language'); ?>"
                    name="<?php echo $this->get_field_name('language'); ?>">
                <option value="de" <?php if ($language == 'de') {
                    echo 'selected';
                } ?> >Deutsch
                </option>
                <option value="en" <?php if ($language == 'en') {
                    echo 'selected';
                } ?>>Englisch
                </option>
                <option value="fr" <?php if ($language == 'fr') {
                    echo 'selected';
                } ?>>Franzsösisch
                </option>
                <option value="es" <?php if ($language == 'es') {
                    echo 'selected';
                } ?>>Spanisch
                </option>
                <option value="ca" <?php if ($language == 'ca') {
                    echo 'selected';
                } ?>>Katalanisch
                </option>
            </select>
        </p>

    <?php
    }

// Updating widget replacing old instances with new
    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['enquiryEmail'] = (!empty($new_instance['enquiryEmail'])) ? strip_tags($new_instance['enquiryEmail']) : '';
        $instance['language'] = (!empty($new_instance['language'])) ? strip_tags($new_instance['language']) : '';
        return $instance;
    }

    /**
     * @param $enquiryFormValues
     * @param $enquiryFormErrors
     * @return string
     */
    public function getEnquiryForm($enquiryFormValues, $enquiryFormErrors)
    {
        $sourceLanguage = __('Source language', 'traduset');
        $targetLanguage = __('Target language', 'traduset');
        $customerName = __('Name', 'traduset');
        $customerEmail = __('E-Mail', 'traduset');
        $uploadFile = __('Upload file', 'traduset');
        $sendForm = __('Send', 'traduset');
        $uploadFileMessage = __('uploadFileMessage', 'traduset');
        $successMessage = __('successMessage', 'traduset');

        $form = '<form action="' . $_SERVER['PHP_SELF'] . '" method="post" name="expressEnquiry" enctype="multipart/form-data" id="expressEnquiryForm">
            <fieldset>

            <label>' . $sourceLanguage . '</label>
            <input type="text" name="sourceLanguage" value="' . $enquiryFormValues['sourceLanguage'] . '" required="required" ' . $enquiryFormErrors['sourceLanguageErrorClass'] . ' >' .
            $enquiryFormErrors['sourceLanguage'] .

            '<label>' . $targetLanguage . '</label>
            <input type="text" name="targetLanguage" value="' . $enquiryFormValues['targetLanguage'] . '" required="required" ' . $enquiryFormErrors['targetLanguageErrorClass'] . ' >' .
            $enquiryFormErrors['targetLanguage'] .

            '<label>' . $customerName . '</label>
            <input type="text" name="customerName" value="' . $enquiryFormValues['customerName'] . '" required="required" ' . $enquiryFormErrors['customerNameErrorClass'] . ' >' .
            $enquiryFormErrors['customerName'] .

            '<label>' . $customerEmail . '</label>
            <input type="email" name="customerEmail" value="' . $enquiryFormValues['customerEmail'] . '" required="required" ' . $enquiryFormErrors['customerEmailErrorClass'] . '  >' .
            $enquiryFormErrors['customerEmail'] .
            '<label>' . $uploadFile . '<span id="exclamation_mark">!</span>
            </label>
            <div class="tooltip">
            <div>' .
            $uploadFileMessage .
            '</div>
            </div>

            <input type="file" multiple  name="uploadfile[]" class="fileUpload" id="expressEnquiryUpload" >' .
            $enquiryFormErrors['uploadFile'] .
            '
            <div class="progress">
                <div class="progressbar"></div >
                <div class="percent">0%</div >
            </div>
            <input type="submit" name="submitExpressEnquiry" value="' . $sendForm . '" name="send" id="expressEnquirySubmit">

            </fieldset>
            </form>
            <div id="status"></div>

            <script>

    $(function() {
// initialize tooltip
        $("span#exclamation_mark").tooltip({
            // tweak the position
            position: \'top center\',
            // use the "slide" effect
            effect: \'slide\'

        });
    });

</script>';
        $progressJQuery = '
        <script>
        $(function() {

    var progressbar = jQuery(\'.progressbar\');
    var percent = jQuery(\'.percent\');
    var status = jQuery(\'#status\');
    var progress = jQuery(\'.progress\');
    var expressEnquiryForm = jQuery(\'#expressEnquiryForm\');
    var expressEnquirySubmit = jQuery(\'#expressEnquirySubmit\');

    jQuery(\'form\').ajaxForm({
        beforeSend: function() {
        status.empty();
        var percentVal = \'0%\';
        progressbar.width(percentVal);
        percent.html(percentVal);
    },
        uploadProgress: function(event, position, total, percentComplete) {
        var percentVal = percentComplete + \'%\';
        expressEnquirySubmit.hide();
        progress.css("display", "block");
        progressbar.width(percentVal);
        percent.html(percentVal);
    },
        complete: function() {
        status.html(\'<div class=\"success\"><h2>'.$successMessage.'</div>\');
        expressEnquiryForm.fadeOut( "slow" );
    }
    });
});
</script>';

        return $form . $progressJQuery;
    }
} // Class wpb_widget ends here

// Register and load the widget
function traduset_enquiry_load_widget()
{
    register_widget('traduset_enquiry_widget');
}

add_action('widgets_init', 'traduset_enquiry_load_widget');