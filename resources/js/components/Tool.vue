<template>
  <div class="flex" v-if="Object.keys(transactions).length > 0">
    <div class="flex-1 m-1" v-for="(showModal, transaction) in transactions">
      <button class="bg-50 w-full btn m-1 p-2 rounded" :class="classes(transaction)" @click.prevent="openModal(transaction)">{{transaction}}</button>
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
    No action required
  </div>
</template>

<script>

  export default {
    props: ['resourceName', 'resourceId', 'field'],

    data() {
      return {
        transactions: this.field.transactions,
        reason:''
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
            resourceId: self.resourceId,
          },
        })
      },

      reject: function () {
        console.log('reject')
      },
      openModal: function (transaction) {
        if (Object.keys(this.reasons(transaction)).length > 0) {
          this.transactions[transaction] = true;
        } else {
          this.action(transaction);
        }
      },
    },
  }
</script>
