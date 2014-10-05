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
        //max upload size 10mb
        $maxUploadSize = 10485760;

// before and after widget arguments are defined by themes
        echo $args['before_widget'];
        if (!empty($title))
            echo $args['before_title'] . $title . $args['after_title'];


// This is where you run the code and display the output
        $enquiryFormValues = array();
        $enquiryFormErrors = array();


//if form is submited check input
        if (!empty($_POST)) {


            $enquiryFormValues['sourceLanguage'] = $_POST['sourceLanguage'] = isset($_POST['sourceLanguage']) ? htmlentities($_POST['sourceLanguage']) : '';
            $enquiryFormValues['targetLanguage'] = $_POST['targetLanguage'] = isset($_POST['targetLanguage']) ? htmlentities($_POST['targetLanguage']) : '';
            $enquiryFormValues['customerName'] = $_POST['customerName'] = isset($_POST['customerName']) ? htmlentities($_POST['customerName']) : '';
            $enquiryFormValues['certified'] = $_POST['certified'] = isset($_POST['certified']) ? htmlentities($_POST['certified']) : '';

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
                    $invalidEmailMessage = __('Email address seems invalid.', 'traduset');
                    $enquiryFormErrors['customerEmail'] = "<div class=\"error\" id=\"emailError\">" . $invalidEmailMessage . "</div>";
                    $enquiryFormErrors['customerEmailErrorClass'] = ' class="error" ';
                }
                $enquiryFormValues['customerEmail'] = isset($_POST['customerEmail']) ? htmlentities($_POST['customerEmail']) : '';
            }

            if (count($enquiryFormErrors) == 0) {

                $maxAllowdUploadSize =
                $allowed = array('pdf', 'gif', 'jpg', 'JPG', 'png', 'docx', 'doc', 'xls', 'xlsx', 'ppt', 'pptx', 'ods', 'csv', 'txt', 'pages', 'rtf');
                $attachments = array();

                $fileSizeTotal = 0;

                foreach ($_FILES['uploadfile']['tmp_name'] as $key => $file_tmp) {
                    //Get the temp file path


                    $file_name = $_FILES['uploadfile']['name'][$key];

                    $error = $_FILES['uploadfile']['error'][$key];
                    $fileSizeTotal = $_FILES['uploadfile']['size'][$key] + $fileSizeTotal;

                    if (!$file_name)
                        break;
                    if ($fileSizeTotal > $maxUploadSize) {
                        $maxFileSizeMessage = __('This file is too large. You can upload max 12MB.', 'traduset');
                        $enquiryFormErrors['uploadFile'] = "<div class=\"error\" id=\"uploadError\">" . $maxFileSizeMessage . "</div>";
                        break;
                    }

                    if ($error == 0) {
                        $ext = pathinfo($file_name, PATHINFO_EXTENSION);
                        if (!in_array($ext, $allowed)) {
                            $invalidFileFormatMessage = __('This file type is not allowed.', 'traduset');
                            $enquiryFormErrors['uploadFile'] = "<div class=\"error\" id=\"uploadError\">" . $invalidFileFormatMessage . "</div>";
                        } else {
                            //keine Fehler, file wird in upload dir beweget und nur beim submit des Forms
                            if (isset ($_POST["submitExpressEnquiry"]) && $file_tmp != "") {
                                $new_file_path = $base_dir . $file_name;
                                if (move_uploaded_file($file_tmp, $new_file_path)) {
                                    array_push($attachments, $new_file_path);
                                } else {
                                    //file konnte nicht bewegt werden
                                    $uploadErrorMessage = __('Failed to upload file. Error occurred.', 'traduset');
                                    $enquiryFormErrors['uploadFile'] = "<div class=\"error\" id=\"uploadError\">" . $uploadErrorMessage . "</div>";
                                }
                            }


                        }
                    } else {
                        if ($error == 1 || $error == 2) {
                            $maxFileSizeMessage = __('This file is too large. You can upload max 10MB.', 'traduset');
                            $enquiryFormErrors['uploadFile'] = "<div class=\"error\" id=\"uploadError\">" . $maxFileSizeMessage . "</div>";
                        } elseif ($error == 3) {
                            $invalidFileFormatMessage = __('This file type is not allowed. Please choose an other', 'traduset');
                            $enquiryFormErrors['uploadFile'] = "<div class=\"error\" id=\"uploadError\">" . $invalidFileFormatMessage . "</div>";
                        } elseif ($error > 3) {
                            $uploadErrorMessage = __('Failed to upload file. Error occurred.', 'traduset');
                            $enquiryFormErrors['uploadFile'] = "<div class=\"error\" id=\"uploadError\">" . $uploadErrorMessage . "</div>";
                        }
                    }
                }
            }
            if (count($enquiryFormErrors) > 0) {
                $content = $this->getEnquiryForm($enquiryFormValues, $enquiryFormErrors, $maxUploadSize);
            } else {
                if (isset ($_POST["submitExpressEnquiry"])) {
                    $headers = 'From: Taduset Übersetzungsbüro <info@traduset.de>' . "\r\n";
                    $subject = 'Traduset Expressanfrage von ' . htmlentities($_POST['customerName']);

                    $format = "Expressanfrage

Von: %s, %s
Ausgangssprache: %s
Zielsprache: %s
beglaubigt: %s

---
Diese E-Mail wurde über das Expressformular von traduset.de gesendet";

                    $message = sprintf($format, htmlentities($_POST['customerName']), $_POST['customerEmail'], htmlentities($_POST['sourceLanguage']), htmlentities($_POST['targetLanguage']), $_POST['certified']);

                    if (!filter_var($enquiryEmail, FILTER_VALIDATE_EMAIL))
                        $enquiryEmail = 'info@traduset.de';

                    if (wp_mail($enquiryEmail, $subject, $message, $headers, $attachments)) {
                        $successMessage = __('Your message was sent successfully. Thanks.', 'traduset');
                        $content = "<div class='success'>" . $successMessage . "</div>";

                        //files nach dem emailvesand wieder löschen
                        foreach ($attachments as $attachment) {
                            unlink($attachment);
                        }
                    } else {
                        $sendErrorMessage = __('Failed to send your message. Please try later or contact the administrator by another method.', 'traduset');
                        $content = "<div class=\"error\">" . $sendErrorMessage . "</div>";
                    }
                }
            }
        } else {
            $content = $this->getEnquiryForm($enquiryFormValues, $enquiryFormErrors, $maxUploadSize);
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
                <option value="it" <?php if ($language == 'it') {
                    echo 'selected';
                } ?>>Italienisch
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
    public function getEnquiryForm($enquiryFormValues, $enquiryFormErrors, $maxUploadSize)
    {
        $sourceLanguage = __('Source language', 'traduset');
        $targetLanguage = __('Target language', 'traduset');
        $customerName = __('Name', 'traduset');
        $customerEmail = __('E-mail', 'traduset');
        $uploadFile = __('Upload file', 'traduset');
        $sendForm = __('Send', 'traduset');
        $uploadFileMessage = __('If you press the Control-Key you can choose more then one file. Valid file types are: pdf, gif, jpg, png, docx, doc, xls, xlsx, ppt, pptx, ods, csv, txt, pages, rtf.', 'traduset');
        $successMessage = '<div class=\"success\"><h2>' . __('Your message was sent successfully. Thanks.', 'traduset') . '</h2></div>';
        $missingFieldMessage = __('Please fill the required field.', 'traduset');
        $invalidEmailMessage = __('Email address seems invalid.', 'traduset');
        $missingEmailMessage = __('We need your email address to contact you.', 'traduset');
        $chooseFile = __('Choose file(s)', 'traduset');
        $selected = __('selected', 'traduset');
        $maxFileSizeMessage = __('This file is too large. You can upload max 10MB.', 'traduset');
        $fileLabel = __('file', 'traduset');
        $filesLabel = __('files', 'traduset');
        $noFiles = __('no files choosen','traduset');
        $certified = __('certified','traduset');
        $yes = __('yes', 'traduset');
        $no = __('no', 'traduset');


        $form = '<form action="' . $_SERVER['PHP_SELF'] . '" method="post" name="expressEnquiry" enctype="multipart/form-data" id="expressEnquiryForm">
            <fieldset>

            <label  for="sourceLanguage">' . $sourceLanguage . '</label>
            <input type="text" id="sourceLanguage" name="sourceLanguage"  value="' . $enquiryFormValues['sourceLanguage'] . '"  ' . $enquiryFormErrors['sourceLanguageErrorClass'] . ' >' .
            $enquiryFormErrors['sourceLanguage'] .

            '<label>' . $targetLanguage . '</label>
            <input type="text" name="targetLanguage" value="' . $enquiryFormValues['targetLanguage'] . '" required="required" ' . $enquiryFormErrors['targetLanguageErrorClass'] . ' >' .
            $enquiryFormErrors['targetLanguage'] .
            '<span class="radioset">'.$certified.': '.
            $yes.': <input type="radio" name="certified" value="ja"> '.
            $no.': <input type="radio" name="certified" value="nein"></span>'.

            '<label>' . $customerName . '</label>
            <input type="text" name="customerName" value="' . $enquiryFormValues['customerName'] . '" required="required" ' . $enquiryFormErrors['customerNameErrorClass'] . ' >' .
            $enquiryFormErrors['customerName'] .

            '<label>' . $customerEmail . '</label>
            <input type="email" name="customerEmail" id="emailInput" value="' . $enquiryFormValues['customerEmail'] . '" required="required" ' . $enquiryFormErrors['customerEmailErrorClass'] . '  >' .
            $enquiryFormErrors['customerEmail'] .
            '<label>' . $uploadFile . '<span id="exclamation_mark">!</span>
            </label>
            <div class="tooltip">
            <div>' .
            $uploadFileMessage .
            '</div>
            </div>
            <input type="hidden" name="MAX_FILE_SIZE" value="' . $maxUploadSize . '" />
            <div class="fileUpload">
            <div class="uploadMessage"></div>
            <input type="file" multiple  name="uploadfile[]" class="upload" id="expressEnquiryUpload">
            <span class="clearfix">
            <span class="uploadButton">'.$chooseFile.'</span>
            <span class="uploadValue"></span>
            </span>
            ' .
            $enquiryFormErrors['uploadFile'] .
            '   <div class="progress">
                    <div class="progressbar"></div >
                    <div class="percent">0%</div >
                </div>
            </div>
            <br />
            <input type="submit" name="submitExpressEnquiry" value="' . $sendForm . '" name="send" id="expressEnquirySubmit">

            </fieldset>
            </form>
            <div id="status"></div>';

        $progressJQuery = '
        <script>
        jQuery(\'#expressEnquiryUpload\').on(\'change\',function(){

            var files = jQuery("#expressEnquiryUpload")[0].files;
            var fileCount = files.length;
            var totalSize = 0;
            var maxSize = ' . $maxUploadSize . ';


            for (var i = 0; i < fileCount; i++){

                if(jQuery.browser.msie){
                    var objFSO = new ActiveXObject("Scripting.FileSystemObject");
                    var sPath = $("#flUpload")[0].value;
                    var objFile = objFSO.getFile(sPath);
                    totalSize = totalSize + objFile.size;

                }else{
                    totalSize = totalSize + files[i].size;
                   // console.log(files[i].type);
                }
            }
            if (totalSize > maxSize){
                var maxFileSizeMessage = \'<div class="error">' . $maxFileSizeMessage . '</div>\';
                jQuery(\'.uploadValue\').html(\'\').append(maxFileSizeMessage);
                jQuery(\'#expressEnquirySubmit\').attr(\'disabled\', \'disabled\');

            }else{
                var fileLabel = \'' . $fileLabel . '\';
                var filesLabel = \'' . $filesLabel . '\';

                jQuery(\'#expressEnquirySubmit\').removeAttr(\'disabled\');

                totalSize = totalSize / 1024;

                if (totalSize / 1024 > 1){
                    totalSize = (Math.round((totalSize / 1024) * 100) / 100)
                    totalSize = totalSize + " MB";
                }else{
                    totalSize = (Math.round(totalSize * 100) / 100)
                    totalSize = totalSize + " KB";
                }

                var message;
                switch (fileCount) {
                    case 0:
                    message = \''.$noFiles.'\';
                    break;
                    case 1:
                    message = fileCount + \' \' + fileLabel + \', \' + totalSize;
                    break;
                    default:
                    message = fileCount + \' \' + filesLabel + \', \' + totalSize;
                    break;
                 }

                message = "<strong>'.$selected.'</strong><br />" + message;

                jQuery(\'.uploadValue\').html(\'\').append(message);
            }




        });

        jQuery(function() {

 // initialize tooltip
        jQuery("span#exclamation_mark").tooltip({
            // tweak the position
            position: \'top center\',
            // use the "slide" effect
            effect: \'slide\'

        });

    var progressbar = jQuery(\'.progressbar\');
    var percent = jQuery(\'.percent\');
    var status = jQuery(\'#status\');
    var progress = jQuery(\'.progress\');
    var expressEnquiryForm = jQuery(\'#expressEnquiryForm\');
    var expressEnquirySubmit = jQuery(\'#expressEnquirySubmit\');

    jQuery("#expressEnquiryForm").validate({
    rules: {
        sourceLanguage: "required",
        targetLanguage: "required",
        customerEmail: {
            required: true,
            email: true
        }
    },
    messages: {
        sourceLanguage: "' . $missingFieldMessage . '",
        targetLanguage: "' . $missingFieldMessage . '",
        customerName: "' . $missingFieldMessage . '",
        customerEmail: {
            required: "' . $missingEmailMessage . '",
            email: "' . $invalidEmailMessage . '"
            }
        }
    });


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
        complete: function(xhr) {
            var responseText =  xhr.responseText ;
            var content = \'' . $successMessage . '\';

            var enquiryFormError = jQuery(responseText).find("div.error").html();

            if (enquiryFormError){
                expressEnquirySubmit.show();
                progress.fadeOut();
                var emailError = jQuery(responseText).find("#emailError");
                if(emailError.length > 0){
                    jQuery(\'#emailInput\').css("border", "1px solid #ff0000");
                }else{
                    jQuery(\'#emailInput\').css("border", "none");
                }

                var uploadError = jQuery(responseText).find("#uploadError");

                if(uploadError.length > 0){
                    jQuery(\'#expressEnquiryUpload\').css("border", "1px solid #ff0000");
                }else{
                    jQuery(\'#expressEnquiryUpload\').css("border", "none");
                }


                content = \'<div class=\"error\">\' + enquiryFormError + \'</div>\';

            }else{
                expressEnquiryForm.fadeOut( "slow" );
            }

            status.html(content);
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

function traduset_enquiry_scripts()
{
    wp_enqueue_script('jquery.form.min', get_template_directory_uri() . '/assets/js/jquery.form.min.js', array(), '20140807', true);

    wp_enqueue_script('jquery.validate.min', get_template_directory_uri() . '/assets/js/jquery.validate.min.js', array(), '20140807', true);

    wp_enqueue_script('additional-methods.min', get_template_directory_uri() . '/assets/js/additional-methods.min.js', array(), '20140807', true);

}

;

add_action('wp_enqueue_scripts', 'traduset_enquiry_scripts');
