export default {
  /**
   * Update the default tab view.
   *
   * @param {string} view
   */
  updateDefaultProfileView(view) {
    axios.post('settings/updateDefaultProfileView', { name: view })
      .then(response => {
        this.global_profile_default_view = view;
      });
  },

  /**
   * Fix avatar in case img is on error.
   *
   * @param {event} event
   */
  fixAvatarDisplay(event) {
    event.srcElement.classList = ['hidden'];
    event.srcElement.nextElementSibling.classList.remove('hidden');
  },

  /**
   * Update pattern visibility based on pattern type selection.
   */
  updatePatternVisibility() {
    // This method is called when pattern type changes
    // The visibility is handled by Vue's v-show directives
  }
};
