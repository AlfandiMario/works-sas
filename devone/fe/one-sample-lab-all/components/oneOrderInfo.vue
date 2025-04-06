<template>
  <div v-show="show" class="orderbox">
    <h3>
      Daftar Pemeriksaan
      <v-progress-circular
        style="height: 20px"
        v-show="loading"
        :indeterminate="true"
      >
      </v-progress-circular>
    </h3>

    <div class="px" v-for="order in orders">
      {{ order.name }}
    </div>
  </div>
</template>

<style scoped>
.orderbox {
  z-index: 0;
  background-color: rgba(230, 255, 230, 0.9);
  color: #004d00;
  padding: 20px;
  border-radius-top: 10px;
  overflow-y: scroll;
  overflow-x: hidden;
}
.px {
  padding: 5px;
  width: 280px;
  border: 1px solid #004d00;
  display: inline-block;
  margin: 0px 2px 0px 2px;
}
</style>

<script>
let ts = "?ts=" + moment().format("YYYYMMDDHHmmss");
module.exports = {
  data() {
    return {
      orderHeaderID: 0,
    };
  },
  components: {
    //'one-fpp':httpVueLoader('../../../../apps/components/oneFpp.vue' + ts),
  },
  computed: {
    orders() {
      return this.$store.state.order_info.orders;
    },
    loading() {
      return this.$store.state.order_info.loading;
    },
    show: {
      get() {
        return this.$store.state.order_info.show;
      },
      set(v) {
        return this.$store.commit("order_info/update_show", v);
      },
    },
    sel_patient() {
      return this.$store.state.samplecall.selected_patient;
    },
  },
  methods: {},
  watch: {
    async sel_patient(n, o) {
      this.$store.dispatch("order_info/search", { id: n.T_OrderHeaderID });
      this.orderHeaderID = n.T_OrderHeaderID;
    },
  },
};
</script>
