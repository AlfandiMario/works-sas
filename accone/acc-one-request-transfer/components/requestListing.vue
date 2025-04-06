<template>
  <div style="width: 100%">

    <add-transfer-request-dialog></add-transfer-request-dialog>

    <add-transfer-detail-dialog></add-transfer-detail-dialog>

    <v-layout row wrap>
      <v-flex xs6 class="left" fill-height pa-1>
        <v-toolbar color="primary" dark>
          <v-toolbar-title class="white--text">LISTING REQUEST TRANSFER</v-toolbar-title>
          <v-spacer></v-spacer>
          <v-btn icon @click="openDialogRequest()">
            <v-icon>add_box</v-icon>
          </v-btn>
        </v-toolbar>

        <v-card class="pa-2">
          <div class="d-flex justify-start align-center" style="gap: 1rem">
            <v-menu v-model="menuStartDate" :close-on-content-click="false" transition="scale-transition"
              :nudge-right="40" lazy offset-y max-width="290px" min-width="290px">
              <template v-slot:activator="{ on }">
                <v-text-field v-model="startDateFormatted" label="Tanggal Awal" readonly style="max-width: 250px"
                  outline hide-details v-on="on" @blur="dStartDate = deFormatedDate(startDateFormatted)"></v-text-field>
              </template>
              <v-date-picker no-title v-model="fStartDate" @input="menuStartDate = false"></v-date-picker>
            </v-menu>
            <v-menu v-model="menuEndDate" :close-on-content-click="false" transition="scale-transition"
              :nudge-right="40" lazy offset-y max-width="290px" min-width="290px">
              <template v-slot:activator="{ on }">
                <v-text-field v-model="endDateFormatted" label="Tanggal Akhir" readonly style="max-width: 250px" outline
                  hide-details v-on="on" @blur="dEndDate = deFormatedDate(endDateFormatted)"></v-text-field>
              </template>
              <v-date-picker no-title v-model="fEndDate" @input="menuEndDate = false"></v-date-picker>
            </v-menu>
            <v-autocomplete v-model="xBranch" :items="branchOptions" :search-input.sync="searchBranch" return-object
              item-text="M_BranchName" label="Cabang" style="max-width: 250px" outline hide-details></v-autocomplete>
            <v-autocomplete v-model="xStatus" :items="statusOptions" :search-input.sync="searchStatus" return-object
              item-text="title" label="Status" style="max-width: 250px" outline hide-details></v-autocomplete>
            <v-text-field v-model="fSearch" label="Cari..." placeholder="NO. TF" style="max-width: 250px" outline
              hide-details></v-text-field>
            <v-btn dark color="primary" style="min-width: 50px; height: 50px; margin: 0" @click="mainTableSearch()">
              <v-icon>search</v-icon>
            </v-btn>
          </div>
        </v-card>

        <v-card class="pa-2 mt-2">
          <v-layout row style="max-height: 600px; overflow: auto">
            <v-flex xs12 pl-2 pr-2 pt-2 pb-2>
              <v-data-table :headers="headers" :items="mainTable" :loading="mainTableLoading" hide-actions
                class="elevation-1">
                <template slot="items" slot-scope="props">
                  <td class="text-xs-left pa-2" v-bind:class="{ 'amber lighten-4': isSelected(props.item) }"
                    @click="selectMe(props.item)">
                    {{ deFormatedDate(props.item.T_GoodsTransferDate) }}
                  </td>
                  <td class="text-xs-left pa-2" v-bind:class="{ 'amber lighten-4': isSelected(props.item) }"
                    @click="selectMe(props.item)">
                    {{ props.item.M_BranchName }}
                  </td>
                  <td class="text-xs-center pa-2" v-bind:class="{ 'amber lighten-4': isSelected(props.item) }"
                    @click="selectMe(props.item)">
                    <div style="
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                      ">
                      {{ props.item.T_GoodsTransferNum }}
                    </div>
                  </td>
                  <td class="text-xs-left pa-2" v-bind:class="{ 'amber lighten-4': isSelected(props.item) }"
                    @click="selectMe(props.item)">
                    <v-chip small :text-color="props.item.T_GoodsTransferStatus === 'Draft'
                      ? 'black'
                      : 'white'
                      " :color="getChipStatusColor(
                        props.item.T_GoodsTransferStatus
                      )
                        ">
                      {{ props.item.T_GoodsTransferStatus }}
                    </v-chip>
                  </td>
                  <template v-if="props.item.T_GoodsTransferStatus == 'Draft'">
                    <td class="text-xs-center pa-2" v-bind:class="{
                      'amber lighten-4': isSelected(props.item),
                    }">
                      <v-icon small @click="updateRequest(props.item)">edit</v-icon>
                      <v-icon small @click="deleteRequest(props.item)">delete</v-icon>
                    </td>
                  </template>
                  <template v-else>
                    <td class="text-xs-center pa-2" v-bind:class="{
                      'amber lighten-4': isSelected(props.item),
                    }"></td>
                  </template>

                </template>
              </v-data-table>
            </v-flex>
          </v-layout>

          <v-divider></v-divider>

          <v-pagination style="margin-top: 10px; margin-bottom: 10px" v-model="mainTableCurrentPage"
            :length="mainTableTotalPage"></v-pagination>
        </v-card>
      </v-flex>

      <!-- Tab Samping Kanan -->
      <v-flex xs6 class="right" fill-height pa-1>
        <v-card>
          <v-card-text>
            <v-layout row wrap>
              <v-flex xs12>
                <v-layout row wrap>
                  <v-flex xs11>
                    <v-layout row wrap>
                      <v-flex xs12>
                        <div>
                          <span>No. TR : </span>
                          <span>{{
                            selectedMainTable.T_GoodsTransferNum
                          }}</span>
                        </div>
                      </v-flex>
                      <v-flex xs12>
                        <div>
                          <span>Tanggal : </span>
                          <span>{{
                            deFormatedDate(
                              selectedMainTable.T_GoodsTransferDate
                            )
                          }}</span>
                        </div>
                      </v-flex>
                      <v-flex xs12>
                        <div>
                          <span>Keterangan : </span>
                          <span>{{
                            selectedMainTable.T_GoodsTransferNotes
                          }}</span>
                        </div>
                      </v-flex>
                    </v-layout>
                  </v-flex>
                  <v-flex xs1>
                    <v-btn color="primary" flat icon :disabled="selectedMainTable.T_GoodsTransferStatus !==
                      'Draft'
                      " @click="openDialogDetail()">
                      <v-icon>add_box</v-icon>
                    </v-btn>
                  </v-flex>
                </v-layout>
              </v-flex>

              <v-flex xs12 pt-2 pb-2>
                <v-layout row wrap>
                  <v-flex xs12>
                    <v-data-table :headers="detailHeaders" :items="detailTable" :loading="detailTableLoading"
                      hide-actions class="elevation-1">
                      <template slot="items" slot-scope="props">
                        <td class="text-xs-left pa-2">
                          {{ props.item.NatItemDesc }}
                        </td>
                        <td class="text-xs-left pa-2">
                          <!-- {{ props.item.StockQty}} -->
                          100 Buah
                        </td>
                        <td class="text-xs-left pa-2">
                          <v-text-field v-model="props.item.PurchaseRequestDirectDetailAmountRequest"
                            :append="'/' + (props.item.TotalReqDetailQty - TotalFlagQty || 0)" outlined
                            hide-details></v-text-field>
                        </td>
                        <template v-if="selectedMainTable.PurchaseRequestDirectStatus === 'Draft'">
                          <td class="text-xs-center pa-2">
                            <v-icon small @click="updateDetail(props.item)">edit</v-icon>
                            <v-icon small @click="deleteDetail(props.item)">delete</v-icon>
                          </td>
                        </template>
                        <template v-else>
                          <td class="text-xs-center pa-2"></td>
                        </template>
                      </template>
                    </v-data-table>
                  </v-flex>
                </v-layout>
              </v-flex>
              <div style="width: 100%" class="d-flex justify-end">
                <template v-if="
                  ['Draft', 'Pending'].includes(
                    selectedMainTable.PurchaseRequestDirectStatus
                  )
                ">
                  <v-btn color="primary" dark style="max-width: 200px" class="ml-5" :disabled="!detailTable.length"
                    @click="
                      orderRequest(
                        selectedMainTable.PurchaseRequestDirectID,
                        selectedMainTable.PurchaseRequestDirectStatus
                      )
                      ">
                    {{
                      selectedMainTable.PurchaseRequestDirectStatus !== "Draft"
                        ? "Pending"
                        : "Request Purchase"
                    }}
                  </v-btn>
                </template>
              </div>
            </v-layout>
          </v-card-text>
        </v-card>
      </v-flex>
    </v-layout>

    <one-dialog-alert :status="dialogAlertDeleteRequest" :msg="msgAlertDeleteRequest"
      @forget-dialog-alert="dialogAlertDeleteRequest = false"
      @close-dialog-alert="closeAndDeleteRequest()"></one-dialog-alert>

    <one-dialog-alert :status="dialogAlertDeleteDetail" :msg="msgAlertDeleteRequest"
      @forget-dialog-alert="dialogAlertDeleteDetail = false"
      @close-dialog-alert="closeAndDeleteDetail()"></one-dialog-alert>
  </div>
</template>

<style scoped>
.date-type-table {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.table.v-table tbody td,
table.v-table tbody th {
  height: 40px;
}

.table.v-table thead tr {
  height: 40px;
}

.scroll-container {
  scroll-padding: 50px 0 0 50px;
}
</style>

<script>
module.exports = {
  components: {
    "add-transfer-request-dialog": httpVueLoader("./addTransferRequestDialog.vue"),
    "add-transfer-detail-dialog": httpVueLoader("./addTransferDetailDialog.vue"),
    "one-dialog-alert": httpVueLoader("../../common/oneDialogAlert.vue"),
    "one-dialog-error": httpVueLoader("../../common/oneDialogError.vue"),
  },
  mounted() {
    this.$store.dispatch("transfer/getBranches");
    this.$store.dispatch("transfer/getStatus");
    this.$store.dispatch("transfer/search");

  },
  data() {
    return {
      msgAlertDeleteRequest: "",
      dialogAlertDeleteRequest: false,
      dialogAlertDeleteDetail: false,
      headers: [
        {
          text: "TANGGAL",
          align: "left",
          sortable: false,
          value: "requestDate",
          width: "20%",
          class: "blue lighten-3 white--text",
        },
        {
          text: "TO CABANG",
          align: "left",
          sortable: false,
          value: "requestDate",
          width: "20%",
          class: "blue lighten-3 white--text",
        },
        {
          text: "NO. TRANSFER",
          align: "center",
          sortable: false,
          value: "no",
          width: "20%",
          class: "blue lighten-3 white--text",
        },
        {
          text: "STATUS",
          align: "left",
          sortable: false,
          value: "status",
          width: "15%",
          class: "blue lighten-3 white--text",
        },
        {
          text: "AKSI",
          align: "center",
          sortable: false,
          value: "action",
          width: "20%",
          class: "blue lighten-3 white--text",
        },
      ],
      detailHeaders: [
        {
          text: "ITEM",
          align: "center",
          sortable: false,
          value: "no",
          width: "40%",
          class: "blue lighten-3 white--text",
        },
        {
          text: "STOK GUDANG",
          align: "left",
          sortable: false,
          value: "requestDate",
          width: "25%",
          class: "blue lighten-3 white--text",
        },
        {
          text: "JUMLAH TF",
          align: "left",
          sortable: false,
          value: "requestDate",
          width: "15%",
          class: "blue lighten-3 white--text",
        },
        {
          text: "AKSI",
          align: "left",
          sortable: false,
          value: "requestDate",
          width: "20%",
          class: "blue lighten-3 white--text",
        }
      ],
    };
  },

  computed: {
    branchOptions() {
      return this.$store.state.transfer.branchOptions;
    },
    xBranch: {
      get() {
        return this.$store.state.transfer.xBranch;
      },
      set(val) {
        this.$store.commit("transfer/updateXBranch", val);
        this.$store.dispatch("transfer/search");
      },
    },
    searchBranch: {
      get() {
        return this.$store.state.transfer.searchBranch;
      },
      set(val) {
        this.$store.commit("transfer/updateSearchBranch", val);
      },
    },
    statusOptions() {
      return this.$store.state.transfer.statusOptions;
    },
    xStatus: {
      get() {
        return this.$store.state.transfer.xStatus;
      },
      set(val) {
        this.$store.commit("transfer/updateXStatus", val);
        this.$store.dispatch("transfer/search");
      },
    },
    searchStatus: {
      get() {
        return this.$store.state.transfer.searchStatus;
      },
      set(val) {
        this.$store.commit("transfer/updateSearchStatus", val);
      },
    },
    menuStartDate: {
      get() {
        return this.$store.state.transfer.menuStartDate;
      },
      set(val) {
        this.$store.commit("transfer/updateMenuStartDate", val);
      },
    },
    menuEndDate: {
      get() {
        return this.$store.state.transfer.menuEndDate;
      },
      set(val) {
        this.$store.commit("transfer/updateMenuEndDate", val);
      },
    },
    startDateFormatted() {
      return this.formatDate(this.fStartDate);
    },
    endDateFormatted() {
      return this.formatDate(this.fEndDate);
    },
    dStartDate: {
      get() {
        return this.$store.state.transfer.dStartDate;
      },
      set(val) {
        this.$store.commit("transfer/updateDStartDate", val);
      },
    },
    dEndDate: {
      get() {
        return this.$store.state.transfer.dEndDate;
      },
      set(val) {
        this.$store.commit("transfer/updateDEndDate", val);
      },
    },
    fStartDate: {
      get() {
        return this.$store.state.transfer.fStartDate;
      },
      set(val) {
        this.$store.commit("transfer/updateFStartDate", val);
      },
    },
    fSearch: {
      get() {
        return this.$store.state.transfer.fSearch;
      },
      set(val) {
        this.$store.commit("transfer/updateFSearch", val);
      },
    },


    mainTable() {
      return this.$store.state.transfer.mainTable;
    },
    detailTable() {
      return this.$store.state.transfer.detailTable;
    },
    mainTableLoading() {
      return this.$store.state.transfer.mainTableLoadingStatus === 1;
    },
    detailTableLoading() {
      return this.$store.state.transfer.detailTableLoadingStatus === 1;
    },
    mainTableTotalPage: {
      get() {
        return this.$store.state.transfer.countMainTable;
      },
      set(val) {
        this.$store.commit("transfer/updateCountMainTable", val);
      },
    },
    mainTableCurrentPage: {
      get() {
        return this.$store.state.transfer.mainTableCurrentPage;
      },
      set(val) {
        this.$store.commit("transfer/updateMainTableCurrentPage", val);
        this.$store.commit("transfer/updateMainTableLastId", -1);
        this.$store.dispatch("transfer/search");
      },
    },
    selectedMainTable: {
      get() {
        return this.$store.state.transfer.selectedMainTable;
      },
      set(val) {
        this.$store.commit("updateSelectedMainTable", val);
      },
    },

    fEndDate: {
      get() {
        return this.$store.state.transfer.fEndDate;
      },
      set(val) {
        this.$store.commit("transfer/updateFEndDate", val);
      },
    },
    fStatus: {
      get() {
        return this.$store.state.transfer.fStatus;
      },
      set(val) {
        this.$store.commit("transfer/updateFStatus", val);
      },
    },
    statusOptions() {
      return this.$store.state.transfer.statusOptions;
    },

    xNumber: {
      get() {
        return this.$store.state.transfer.xNumber;
      },
      set(val) {
        this.$store.commit("transfer/updateXNumber", val);
      },
    },
    xPRID: {
      get() {
        return this.$store.state.transfer.xPRID;
      },
      set(val) {
        this.$store.commit("transfer/updateXPRID", val);
      },
    },
    xPRDID: {
      get() {
        return this.$store.state.transfer.xPRDID;
      },
      set(val) {
        this.$store.commit("transfer/updateXPRDID", val);
      },
    },
    xDescriptionDetail() {
      return this.$store.state.transfer.xDescriptionDetail;
    },
    vendors() {
      return this.$store.state.transfer.vendors;
    },
    selectedVendor: {
      get() {
        return this.$store.state.transfer.selectedVendor;
      },
      set(val) {
        this.$store.commit("transfer/updateSelectedVendor", val);
        this.$store.dispatch("transfer/search");
      },
    },
    searchVendor: {
      get() {
        return this.$store.state.transfer.searchVendor;
      },
      set(val) {
        this.$store.commit("transfer/updateSearchVendor", val);
      },
    },
    itemTypes() {
      return this.$store.state.transfer.itemTypes;
    },
    selectedItemType: {
      get() {
        return this.$store.state.transfer.selectedItemType;
      },
      set(val) {
        this.$store.commit("transfer/updateSelectedItemType", val);
        this.$store.dispatch("transfer/search");
      },
    },
    searchItemType: {
      get() {
        return this.$store.state.transfer.searchItemType;
      },
      set(val) {
        this.$store.commit("transfer/updateSearchItemType", val);
      },
    },
  },
  methods: {
    isSelected(selectedreq) {
      return (
        selectedreq.T_GoodsTransferID == this.$store.state.transfer.selectedMainTable.T_GoodsTransferID
      );
    },
    selectMe(selectedreq) {
      this.$store.commit("transfer/updateSelectedMainTable", selectedreq);
      this.$store.dispatch("transfer/searchDetail");
    },
    formatDate(date) {
      if (!date) return null;

      const [year, month, day] = date.split("-");
      return `${day}-${month}-${year}`;
    },
    deFormatedDate(date) {
      if (!date) return null;

      const [day, month, year] = date.split("-");
      return `${year}-${month.padStart(2, "0")}-${day.padStart(2, "0")}`;
    },
    getChipStatusColor(status) {
      switch (status) {
        case "Pending":
          return "warning";
        case "Approved":
          return "success";
        case "Rejected":
          return "error";
        case "Completed":
          return "info";
        case "Paid":
          return "success";
        default:
          return "";
      }
    },
    getChipStatusDetailColor(status) {
      switch (status) {
        case "Pending":
          return "warning";
        case "Ordered":
          return "success";
        case "Received":
          return "info";
        case "Cancelled":
          return "error";
        default:
          return "warning";
      }
    },
    mainTableSearch() {
      this.$store.dispatch("transfer/search");
      this.$store.commit("transfer/updateSelectedMainTable", {});
      this.$store.commit("transfer/updateDetailTable", []);
    },
    openDialogRequest() {
      this.$store.commit(
        "transfer/updateXDateUse",
        moment(new Date()).format("YYYY-MM-DD")
      );
      this.$store.commit("transfer/updateXBranch", "");
      this.$store.commit("transfer/updateXDescription", "");
      this.$store.commit("transfer/updateAct", "new");
      this.$store.commit("transfer/updateDialogRequest", true);
    },
    updateRequest(val) {
      this.$store.dispatch("transfer/branch");
      this.$store.commit(
        "transfer/updateXDateUse",
        val.PurchaseRequestDirectDateUse
      );
      this.$store.commit(
        "transfer/updateXBranch",
        val.PurchaseRequestDirectM_BranchCode
      );
      this.$store.commit(
        "transfer/updateXDescription",
        val.PurchaseRequestDirectDescription
      );
      this.$store.commit("transfer/updateXPRID", val.PurchaseRequestDirectID);
      this.$store.commit("transfer/updateAct", "edit");
      this.$store.commit("transfer/updateDialogRequest", true);
    },
    deleteRequest(data) {
      this.xPRID = data.PurchaseRequestDirectID;
      this.xNumber = data.PurchaseRequestDirectNumber;

      this.msgAlertDeleteRequest = `Yakin, mau hapus Purchase Request [${data.PurchaseRequestDirectNumber}] ?`;
      this.dialogAlertDeleteRequest = true;
    },
    closeAndDeleteRequest() {
      this.$store.dispatch("transfer/deleteRequest", {
        PRID: this.xPRID,
        PRNumber: this.xNumber,
      });
      this.dialogAlertDeleteRequest = false;
    },
    openDialogDetail() {
      this.$store.commit("transfer/updateXDescriptionDetail", "");
      this.$store.commit("transfer/updateXAmountRequest", 0);
      this.$store.commit("transfer/updateXEstimationPrice", 0);
      this.$store.commit("transfer/updateAct", "new");
      this.$store.commit("transfer/updateDialogDetail", true);
    },
    updateDetail(val) {
      this.$store.commit(
        "transfer/updateXDescriptionDetail",
        val.PurchaseRequestDirectDescription
      );
      this.$store.commit(
        "transfer/updateXAmountRequest",
        val.PurchaseRequestDirectDetailAmountRequest
      );
      this.$store.commit(
        "transfer/updateXEstimationPrice",
        val.PurchaseRequestDirectDetailEstimationPrice
      );
      this.$store.commit("transfer/updateSelectedDetailTable", val);
      this.$store.commit("transfer/updateAct", "edit");
      this.$store.commit("transfer/updateDialogDetail", true);
    },
    deleteDetail(data) {
      this.xPRDID = data.PurchaseRequestDirectDetailID;

      this.msgAlertDeleteRequest = `Yakin, mau hapus Request [${data.PurchaseRequestDirectDescription}] ?`;
      this.dialogAlertDeleteDetail = true;
    },
    closeAndDeleteDetail() {
      this.$store.dispatch("transfer/deleteDetail", {
        PRDID: this.xPRDID,
        PRDDescriptionDetail: this.xDescriptionDetail,
        PRID: this.selectedMainTable.PurchaseRequestDirectID,
      });
      this.dialogAlertDeleteDetail = false;
    },
    orderRequest(id, status) {
      if (status === "Draft") {
        this.$store.dispatch("transfer/orderRequest", {
          PRID: id,
        });
      }
    },
    thrSearchStatus: _.debounce(function () {
      this.$store.dispatch("transfer/search")
    }, 1000),
    thrSearchBranch: _.debounce(function () {
      this.$store.dispatch("transfer/search")
    }, 100),
    thrSearchDate: _.debounce(function () {
      this.$store.dispatch("transfer/search")
    }, 1000),
    thrSearch: _.debounce(function () {
      this.$store.dispatch("transfer/search")
    }, 1000),
  },

  watch: {
    searchStatus(val, old) {
      if (val == old) return;
      if (!val) return;
      if (val.length < 1) return;
      if (this.$store.state.transfer.loadingAutocomplete == 1) return;
      this.thrSearchStatus();
    },
    searchBranch(val, old) {
      if (val == old) return;
      if (!val) return;
      if (val.length < 1) return;
      if (this.$store.state.transfer.loadingAutocomplete == 1) return;
      this.thrSearchBranch();
    },
    fStartDate(val, old) {
      this.fStartDate = val
      this.thrSearchDate()
    },
    fEndDate(val, old) {
      this.fEndDate = val
      this.thrSearchDate()
    },
    fSearch(val, old) {
      this.fSearch = val
      this.thrSearch()
    }
  }
};
</script>
