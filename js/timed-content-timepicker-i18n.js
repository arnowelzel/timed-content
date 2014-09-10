/* 	I18n initialization for the JQuery UI Timepicker plugin. 
	
	We feed strings in from the main 'timed-content.php' file so that translators can provide the localized 
	versions from the corresponding .POT file.  We don't expect them to become developers in order to
	provide translations. :)
*/
jQuery(function($){
    $.timepicker.regional['timed-content-i18n'] = {
        hourText: TimedContentJQTimepickerI18n.hourText,
        minuteText: TimedContentJQTimepickerI18n.minuteText,
        amPmText: TimedContentJQTimepickerI18n.amPmText,
        showPeriod: TimedContentJQTimepickerI18n.showPeriod,
        timeSeparator: TimedContentJQTimepickerI18n.timeSeparator,
        closeButtonText: TimedContentJQTimepickerI18n.closeButtonText,
        nowButtonText: TimedContentJQTimepickerI18n.nowButtonText,
        deselectButtonText: TimedContentJQTimepickerI18n.deselectButtonText };
    $.timepicker.setDefaults($.timepicker.regional['timed-content-i18n']);
});