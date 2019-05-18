<template>
  <div class="flex" v-if="Object.keys(transactions).length > 0">
    <!-- Action Confirmation Modal -->
    <!-- <portal to="modals"> -->
    <transition name="fade">
      <component
          :is="action_component.component"
          :working="working"
          v-if="confirmActionModalOpened"
          :selected-resources="selectedResources"
          :resource-name="resourceName"
          :action="action_component"
          :errors="errors"
          @confirm="executeAction"
          @close="confirmActionModalOpened = false"
      />
    </transition>
    <!-- </portal> -->
    <div class="flex-1 m-1" v-for="(showModal, transaction) in transactions">
      <button class="bg-50 w-full btn m-1 p-2 rounded" :class="classes(transaction)" @click.prevent="openModal(transaction)">
        {{transaction}}
      </button>

      <modal v-if="showModal" @modal-close="close(transaction)">
        <form
            class="bg-white rounded-lg shadow-lg overflow-hidden"
            style="width: 460px"
        >
          <slot>
            <div class="p-8">
              <heading :level="2" class="mb-6">{{ __('Choose one of the reasons') }}</heading>
              <p class="text-80 leading-normal">
                <select v-if="reasons(transaction) != 'textarea'" v-model="reason" class="form-control form-select w-full">
                  <option v-for="(item, index) in reasons(transaction)" :value="index">{{ item}}</option>
                </select>
                <textarea v-else v-model="reason" class="form-control form-input-bordered h-auto w-full"></textarea>
              </p>
            </div>
          </slot>

          <div class="bg-30 px-6 py-3 flex">
            <div class="ml-auto">
              <button
                  type="button"
                  data-testid="cancel-button"
                  dusk="cancel-delete-button"
                  @click.prevent="close(transaction)"
                  class="btn text-80 font-normal h-9 px-3 mr-3 btn-link"
              >
                {{ __('Cancel') }}
              </button>
              <button
                  id="confirm-delete-button"
                  ref="confirmButton"
                  data-testid="confirm-button"
                  type="submit"
                  @click="action(transaction)"
                  class="btn btn-default btn-primary"
              >
                OK
              </button>
            </div>
          </div>
        </form>
      </modal>
    </div>
  </div>
  <div v-else>
    لا يوجد إجراء
  </div>
</template>

<script>
  import _ from 'lodash'
  import {Errors, InteractsWithResourceInformation} from 'laravel-nova'

  export default {
    props: ['resourceName', 'resourceId', 'field'],

    data() {
      return {
        working: false,
        transactions: this.field.transactions,
        reason: '',
        selectedResources: [this.resourceId],
        errors: new Errors(),
        action_component: [],
        confirmActionModalOpened: false
      }
    },

    methods: {
      close(transaction) {
        this.transactions[transaction] = false;

        this.$emit('close')
      },

      classes(transaction) {
        return this.field.styles[transaction];
      },

      reasons(transaction) {
        try {
          return this.field.reasons[transaction] || [];
        } catch (e) {
          return [];
        }
      },
      actions(transaction) {
        try {
          return this.field.actions[transaction] || [];
        } catch (e) {
          return [];
        }
      },


      /**
       * Gather the action FormData for the given action.
       */
      actionFormData() {
        return _.tap(new FormData(), formData => {
          formData.append('resources', this.selectedResources)

          _.each(this.action_component.fields, field => {
            field.fill(formData)
          })
        })
      },

      /**
       * Execute the selected action.
       */
      executeAction() {
        var self = this;
        this.working = true

        if (this.selectedResources.length == 0) {
          alert(this.__('Please select a resource to perform this action on.'))
          return
        }

        Nova.request({
          method: 'post',
          url: this.endpoint || `/nova-api/${this.resourceName}/action`,
          params: {
            action: this.action_component.uriKey,
            pivotAction: false,
            search: '',
            filters: '',
            trashed: '',
            viaResource: this.resourceName,
            viaResourceId: this.resourceId,
            viaRelationship: '',
          },
          data: this.actionFormData(),
        })
          .then(response => {
            this.confirmActionModalOpened = false
            this.handleActionResponse(response.data)
            this.working = false

            self.$router.push({
              name: 'index',
              params: {
                resourceName: self.resourceName,
              },
            });
          })
          .catch(error => {
            this.working = false

            if (error.response.status == 422) {
              this.errors = new Errors(error.response.data.errors)
            }
          })
      },

      /**
       * Handle the action response. Typically either a message, download or a redirect.
       */
      handleActionResponse(response) {
        if (response.message) {
          this.$emit('actionExecuted')
          this.$toasted.show(response.message, {type: 'success'})
        } else if (response.deleted) {
          this.$emit('actionExecuted')
        } else if (response.danger) {
          this.$emit('actionExecuted')
          this.$toasted.show(response.danger, {type: 'error'})
        } else if (response.download) {
          let link = document.createElement('a')
          link.href = response.download
          link.download = response.name
          document.body.appendChild(link)
          link.click()
          document.body.removeChild(link)
        } else if (response.redirect) {
          window.location = response.redirect
        } else if (response.openInNewTab) {
          window.open(response.openInNewTab, '_blank')
        } else {
          this.$emit('actionExecuted')
          this.$toasted.show(this.__('The action ran successfully!'), {type: 'success'})
        }
      },

      async action(transaction) {
        var self = this,
          slug = `${this.field.workflow}/${this.resourceId}/${transaction.replace(/\s/g, '_')}/${this.reason}`;

        await Nova.request().get(`/nova-vendor/workflow/${slug}`);

        self.close(transaction);

        self.$toasted.show('Resource successfully changed to ' + transaction, {type: 'success'});

        self.$router.push({
          name: 'index',
          params: {
            resourceName: self.resourceName,
          },
        })
      },

      reject: function () {
        console.log('reject')
      },
      openModal: function (transaction) {
        this.action_component = this.actions(transaction);
        if (Object.keys(this.action_component).length) {
          this.confirmActionModalOpened = true;
        } else {
          this.action(transaction);
        }
        return;
        if (Object.keys(this.reasons(transaction)).length > 0) {
          this.transactions[transaction] = true;
        }
      },
    },


    // computed: {
    //   selectedAction() {
    //     if (this.selectedActionKey) {
    //       return _.find(this.allActions, a => a.uriKey == this.selectedActionKey)
    //     }
    //   },
    //
    //   /**
    //    * Get the query string for an action request.
    //    */
    //   actionRequestQueryString() {
    //     return
    //   },
    //
    //   /**
    //    * Determine if the selected action is a pivot action.
    //    */
    //   selectedActionIsPivotAction() {
    //     return (
    //       this.hasPivotActions &&
    //       Boolean(_.find(this.pivotActions.actions, a => a === this.selectedAction))
    //     )
    //   },
    //
    //   /**
    //    * Get all of the available actions.
    //    */
    //   allActions() {
    //     return this.actions.concat(this.pivotActions.actions)
    //   },
    //
    //   /**
    //    * Get all of the available non-pivot actions for the resource.
    //    */
    //   availableActions() {
    //     return _(this.actions)
    //       .filter(action => {
    //         if (this.selectedResources != 'all') {
    //           return true
    //         }
    //
    //         return action.availableForEntireResource
    //       })
    //       .value()
    //   },
    //
    //   /**
    //    * Determine whether there are any pivot actions
    //    */
    //   hasPivotActions() {
    //     return this.availablePivotActions.length > 0
    //   },
    //
    //   /**
    //    * Get all of the available pivot actions for the resource.
    //    */
    //   availablePivotActions() {
    //     return _(this.pivotActions.actions)
    //       .filter(action => {
    //         if (this.selectedResources != 'all') {
    //           return true
    //         }
    //
    //         return action.availableForEntireResource
    //       })
    //       .value()
    //   },
    // },
  }
</script>
