<template>
  <div style="width: 100%">
    <v-layout row wrap>
      <v-flex xs6 class="left" fill-height pa-1>
        <v-toolbar color="primary" dark>
          <v-toolbar-title class="white--text"
            >PURCHASE REQUEST PO</v-toolbar-title
          >
          <v-spacer></v-spacer>
          <v-btn icon @click="openDialogRequest()">
            <v-icon>add_box</v-icon>
          </v-btn>
        </v-toolbar>
        <v-card class="pa-2">
          <div class="d-flex justify-start align-center" style="gap: 1rem">
            <v-menu
              v-model="menuStartDate"
              :close-on-content-click="false"
              transition="scale-transition"
              :nudge-right="40"
              lazy
              offset-y
              max-width="290px"
              min-width="290px"
            >
              <template v-slot:activator="{ on }">
                <v-text-field
                  v-model="startDateFormatted"
                  label="Tanggal Awal"
                  readonly
                  style="max-width: 250px"
                  outline
                  hide-details
                  v-on="on"
                  @blur="dStartDate = deFormatedDate(startDateFormatted)"
                ></v-text-field>
              </template>
              <v-date-picker
                no-title
                v-model="fStartDate"
                @input="menuStartDate = false"
              ></v-date-picker>
            </v-menu>
            <v-menu
              v-model="menuEndDate"
              :close-on-content-click="false"
              transition="scale-transition"
              :nudge-right="40"
              lazy
              offset-y
              max-width="290px"
              min-width="290px"
            >
              <template v-slot:activator="{ on }">
                <v-text-field
                  v-model="endDateFormatted"
                  label="Tanggal Akhir"
                  readonly
                  style="max-width: 250px"
                  outline
                  hide-details
                  v-on="on"
                  @blur="dEndDate = deFormatedDate(endDateFormatted)"
                ></v-text-field>
              </template>
              <v-date-picker
                no-title
                v-model="fEndDate"
                @input="menuEndDate = false"
              ></v-date-picker>
            </v-menu>
            <v-autocomplete
              v-model="selectedVendor"
              :items="vendors"
              :search-input.sync="searchVendor"
              return-object
              item-text="SupplierName"
              label="Vendor"
              style="max-width: 250px"
              outline
              hide-details
            ></v-autocomplete>
            <v-autocomplete
              v-model="selectedItemType"
              :search-input.sync="searchItemType"
              :items="itemTypes"
              return-object
              item-text="ItemTypeName"
              label="Type"
              style="max-width: 250px"
              outline
              hide-details
            ></v-autocomplete>
            <v-text-field
              v-model="fSearch"
              label="Cari..."
              placeholder="NO. PR"
              style="max-width: 250px"
              outline
              hide-details
            ></v-text-field>
            <v-btn
              dark
              color="primary"
              style="min-width: 50px; height: 50px; margin: 0"
              @click="mainTableSearch()"
            >
              <v-icon>search</v-icon>
            </v-btn>
          </div>
        </v-card>
        <v-card class="pa-2 mt-2">
          <v-layout row style="max-height: 600px; overflow: auto">
            <v-flex xs12 pl-2 pr-2 pt-2 pb-2>
              <v-data-table
                :headers="headers"
                :items="mainTable"
                :loading="mainTableLoading"
                hide-actions
                class="elevation-1"
              >
                <template slot="items" slot-scope="props">
                  <td
                    class="text-xs-center pa-2"
                    v-bind:class="{ 'amber lighten-4': isSelected(props.item) }"
                    @click="selectMe(props.item)"
                  >
                    <div
                      style="
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                      "
                    >
                      {{ props.item.PurchaseRequestNumber }}
                    </div>
                  </td>
                  <td
                    class="text-xs-left pa-2"
                    v-bind:class="{ 'amber lighten-4': isSelected(props.item) }"
                    @click="selectMe(props.item)"
                  >
                      {{ deFormatedDate(props.item.PurchaseRequestDate) }}
                  </td>
                  <td
                    class="text-xs-left pa-2"
                    v-bind:class="{ 'amber lighten-4': isSelected(props.item) }"
                    @click="selectMe(props.item)"
                  >
                      {{ props.item.SupplierName }}
                  </td>
                  <td
                    class="text-xs-left pa-2"
                    v-bind:class="{ 'amber lighten-4': isSelected(props.item) }"
                    @click="selectMe(props.item)"
                  >
                      {{ props.item.ItemTypeName }}
                  </td>
                  <td
                    class="text-xs-left pa-2"
                    v-bind:class="{ 'amber lighten-4': isSelected(props.item) }"
                    @click="selectMe(props.item)"
                  >
                    Rp
                    {{
                      props.item.PurchaseRequestTotal.toString().replace(
                        /\B(?=(\d{3})+(?!\d))/g,
                        "."
                      )
                    }}
                  </td>
                  <td
                    class="text-xs-left pa-2"
                    v-bind:class="{ 'amber lighten-4': isSelected(props.item) }"
                    @click="selectMe(props.item)"
                  >
                    <v-chip
                      small
                      :text-color="
                        props.item.PurchaseRequestStatus === 'Draft'
                          ? 'black'
                          : 'white'
                      "
                      :color="
                        getChipStatusColor(
                          props.item.PurchaseRequestStatus
                        )
                      "
                    >
                      {{ props.item.PurchaseRequestStatus }}
                    </v-chip>
                  </td>
                  <template
                    v-if="props.item.PurchaseRequestStatus == 'Draft'"
                  >
                    <td
                      class="text-xs-center pa-2"
                      v-bind:class="{
                        'amber lighten-4': isSelected(props.item),
                      }"
                    >
                      <v-icon small @click="updateRequest(props.item)"
                        >edit</v-icon
                      >
                      <v-icon small @click="deleteRequest(props.item)"
                        >delete</v-icon
                      >
                    </td>
                  </template>
                  <template v-else>
                    <td
                      class="text-xs-center pa-2"
                      v-bind:class="{
                        'amber lighten-4': isSelected(props.item),
                      }"
                    ></td>
                  </template>
                </template>
              </v-data-table>
            </v-flex>
          </v-layout>
          <v-divider></v-divider>
          <v-pagination
            style="margin-top: 10px; margin-bottom: 10px"
            v-model="mainTableCurrentPage"
            :length="mainTableTotalPage"
          ></v-pagination>
        </v-card>
      </v-flex>
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
                          <span>No. PR : </span>
                          <span>{{
                            selectedMainTable.PurchaseRequestDirectNumber
                          }}</span>
                        </div>
                      </v-flex>
                      <v-flex xs12>
                        <div>
                          <span>Tanggal : </span>
                          <span>{{
                            deFormatedDate(
                              selectedMainTable.PurchaseRequestDirectDate
                            )
                          }}</span>
                        </div>
                      </v-flex>
                      <v-flex xs12>
                        <div>
                          <span>Keterangan : </span>
                          <span>{{
                            selectedMainTable.PurchaseRequestDirectDescription
                          }}</span>
                        </div>
                      </v-flex>
                    </v-layout>
                  </v-flex>
                  <v-flex xs1>
                    <v-btn
                      color="primary"
                      flat
                      icon
                      :disabled="
                        selectedMainTable.PurchaseRequestDirectStatus !==
                        'Draft'
                      "
                      @click="openDialogDetail()"
                    >
                      <v-icon>add_box</v-icon>
                    </v-btn>
                  </v-flex>
                </v-layout>
              </v-flex>
              <v-flex xs12 pt-2 pb-2>
                <v-layout row wrap>
                  <v-flex xs12>
                    <v-data-table
                      :headers="detailHeaders"
                      :items="detailTable"
                      :loading="detailTableLoading"
                      hide-actions
                      class="elevation-1"
                    >
                      <template slot="items" slot-scope="props">
                        <td class="text-xs-center pa-2">
                          {{ props.item.RowNumber }}
                        </td>
                        <td class="text-xs-left pa-2">
                          {{ props.item.PurchaseRequestDirectDescription }}
                        </td>
                        <td class="text-xs-left pa-2">
                          {{
                            props.item.PurchaseRequestDirectDetailAmountRequest
                          }}/{{
                            props.item.PurchaseRequestDirectDetailAmount ?? 0
                          }}
                        </td>
                        <td class="text-xs-left pa-2">
                          Rp
                          {{
                            props.item.PurchaseRequestDirectDetailEstimationPrice.toString().replace(
                              /\B(?=(\d{3})+(?!\d))/g,
                              "."
                            )
                          }}
                        </td>
                        <td class="text-xs-left pa-2">
                          Rp
                          {{
                            (
                              props.item
                                .PurchaseRequestDirectDetailAmountRequest *
                              props.item
                                .PurchaseRequestDirectDetailEstimationPrice
                            )
                              .toString()
                              .replace(/\B(?=(\d{3})+(?!\d))/g, ".")
                          }}
                        </td>
                        <td class="text-xs-left pa-2">
                          <v-chip
                            small
                            text-color="white"
                            :color="
                              getChipStatusDetailColor(
                                props.item.PurchaseRequestDirectDetailStatus
                              )
                            "
                            >{{
                              props.item.PurchaseRequestDirectDetailStatus
                            }}</v-chip
                          >
                        </td>
                        <template
                          v-if="
                            selectedMainTable.PurchaseRequestDirectStatus ===
                            'Draft'
                          "
                        >
                          <td class="text-xs-center pa-2">
                            <v-icon small @click="updateDetail(props.item)"
                              >edit</v-icon
                            >
                            <v-icon small @click="deleteDetail(props.item)"
                              >delete</v-icon
                            >
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
                <template
                  v-if="
                    ['Draft', 'Pending'].includes(
                      selectedMainTable.PurchaseRequestDirectStatus
                    )
                  "
                >
                  <v-btn
                    color="primary"
                    dark
                    style="max-width: 200px"
                    class="ml-5"
                    :disabled="!detailTable.length"
                    @click="
                      orderRequest(
                        selectedMainTable.PurchaseRequestDirectID,
                        selectedMainTable.PurchaseRequestDirectStatus
                      )
                    "
                  >
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

    <add-purchase-request-dialog></add-purchase-request-dialog>
    <add-request-detail-dialog></add-request-detail-dialog>

    <one-dialog-alert
      :status="dialogAlertDeleteRequest"
      :msg="msgAlertDeleteRequest"
      @forget-dialog-alert="dialogAlertDeleteRequest = false"
      @close-dialog-alert="closeAndDeleteRequest()"
    ></one-dialog-alert>

    <one-dialog-alert
      :status="dialogAlertDeleteDetail"
      :msg="msgAlertDeleteRequest"
      @forget-dialog-alert="dialogAlertDeleteDetail = false"
      @close-dialog-alert="closeAndDeleteDetail()"
    ></one-dialog-alert>
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
    "add-purchase-request-dialog": httpVueLoader(
      "./addPurchaseRequestDialog.vue"
    ),
    "add-request-detail-dialog": httpVueLoader("./addRequestDetailDialog.vue"),
    "one-dialog-alert": httpVueLoader("../../common/oneDialogAlert.vue"),
    "one-dialog-error": httpVueLoader("../../common/oneDialogError.vue"),
  },
  data() {
    return {
      msgAlertDeleteRequest: "",
      dialogAlertDeleteRequest: false,
      dialogAlertDeleteDetail: false,
      headers: [
        {
          text: "NO PR",
          align: "center",
          sortable: false,
          value: "no",
          width: "15%",
          class: "blue lighten-3 white--text",
        },
        {
          text: "TANGGAL",
          align: "left",
          sortable: false,
          value: "requestDate",
          width: "15%",
          class: "blue lighten-3 white--text",
        },
        {
          text: "VENDOR",
          align: "left",
          sortable: false,
          value: "requestDate",
          width: "15%",
          class: "blue lighten-3 white--text",
        },
        {
          text: "TIPE",
          align: "left",
          sortable: false,
          value: "requestDate",
          width: "15%",
          class: "blue lighten-3 white--text",
        },
        {
          text: "TOTAL HARGA",
          align: "left",
          sortable: false,
          value: "requestDate",
          width: "15%",
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
          width: "10%",
          class: "blue lighten-3 white--text",
        },
      ],
      detailHeaders: [
        {
          text: "NO",
          align: "center",
          sortable: false,
          value: "no",
          width: "5%",
          class: "blue lighten-3 white--text",
        },
        {
          text: "KETERANGAN",
          align: "left",
          sortable: false,
          value: "requestDate",
          width: "30%",
          class: "blue lighten-3 white--text",
        },
        {
          text: "JUMLAH",
          align: "left",
          sortable: false,
          value: "requestDate",
          width: "5%",
          class: "blue lighten-3 white--text",
        },
        {
          text: "HARGA",
          align: "left",
          sortable: false,
          value: "requestDate",
          width: "20%",
          class: "blue lighten-3 white--text",
        },
        {
          text: "TOTAL HARGA",
          align: "left",
          sortable: false,
          value: "useDate",
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
          align: "left",
          sortable: false,
          value: "action",
          width: "5%",
          class: "blue lighten-3 white--text",
        },
      ],
    };
  },
  mounted() {
    this.$store.dispatch("requester/search");
    this.$store.dispatch("requester/getVendor");
    this.$store.dispatch("requester/getItemType");
  },
  computed: {
    mainTable() {
      return this.$store.state.requester.mainTable;
    },
    detailTable() {
      return this.$store.state.requester.detailTable;
    },
    mainTableLoading() {
      return this.$store.state.requester.mainTableLoadingStatus === 1;
    },
    detailTableLoading() {
      return this.$store.state.requester.detailTableLoadingStatus === 1;
    },
    mainTableTotalPage: {
      get() {
        return this.$store.state.requester.countMainTable;
      },
      set(val) {
        this.$store.commit("requester/updateCountMainTable", val);
      },
    },
    mainTableCurrentPage: {
      get() {
        return this.$store.state.requester.mainTableCurrentPage;
      },
      set(val) {
        this.$store.commit("requester/updateMainTableCurrentPage", val);
        this.$store.commit("requester/updateMainTableLastId", -1);
        this.$store.dispatch("requester/search");
      },
    },
    selectedMainTable: {
      get() {
        return this.$store.state.requester.selectedMainTable;
      },
      set(val) {
        this.$store.commit("updateSelectedMainTable", val);
      },
    },
    menuStartDate: {
      get() {
        return this.$store.state.requester.menuStartDate;
      },
      set(val) {
        this.$store.commit("requester/updateMenuStartDate", val);
      },
    },
    menuEndDate: {
      get() {
        return this.$store.state.requester.menuEndDate;
      },
      set(val) {
        this.$store.commit("requester/updateMenuEndDate", val);
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
        return this.$store.state.requester.dStartDate;
      },
      set(val) {
        this.$store.commit("requester/updateDStartDate", val);
      },
    },
    dEndDate: {
      get() {
        return this.$store.state.requester.dEndDate;
      },
      set(val) {
        this.$store.commit("requester/updateDEndDate", val);
      },
    },
    fStartDate: {
      get() {
        return this.$store.state.requester.fStartDate;
      },
      set(val) {
        this.$store.commit("requester/updateFStartDate", val);
      },
    },
    fEndDate: {
      get() {
        return this.$store.state.requester.fEndDate;
      },
      set(val) {
        this.$store.commit("requester/updateFEndDate", val);
      },
    },
    fStatus: {
      get() {
        return this.$store.state.requester.fStatus;
      },
      set(val) {
        this.$store.commit("requester/updateFStatus", val);
      },
    },
    statusOptions() {
      return this.$store.state.requester.statusOptions;
    },
    fSearch: {
      get() {
        return this.$store.state.requester.fSearch;
      },
      set(val) {
        this.$store.commit("requester/updateFSearch", val);
      },
    },
    xNumber: {
      get() {
        return this.$store.state.requester.xNumber;
      },
      set(val) {
        this.$store.commit("requester/updateXNumber", val);
      },
    },
    xPRID: {
      get() {
        return this.$store.state.requester.xPRID;
      },
      set(val) {
        this.$store.commit("requester/updateXPRID", val);
      },
    },
    xPRDID: {
      get() {
        return this.$store.state.requester.xPRDID;
      },
      set(val) {
        this.$store.commit("requester/updateXPRDID", val);
      },
    },
    xDescriptionDetail() {
      return this.$store.state.requester.xDescriptionDetail;
    },
    vendors() {
      return this.$store.state.requester.vendors;
    },
    selectedVendor: {
      get() {
        return this.$store.state.requester.selectedVendor;
      },
      set(val) {
        this.$store.commit("requester/updateSelectedVendor", val);
        this.$store.dispatch("requester/search");
      },
    },
    searchVendor: {
      get() {
        return this.$store.state.requester.searchVendor;
      },
      set(val) {
        this.$store.commit("requester/updateSearchVendor", val);
      },
    },
    itemTypes() {
      return this.$store.state.requester.itemTypes;
    },
    selectedItemType: {
      get() {
        return this.$store.state.requester.selectedItemType;
      },
      set(val) {
        this.$store.commit("requester/updateSelectedItemType", val);
        this.$store.dispatch("requester/search");
      },
    },
    searchItemType: {
      get() {
        return this.$store.state.requester.searchItemType;
      },
      set(val) {
        this.$store.commit("requester/updateSearchItemType", val);
      },
    },
  },
  methods: {
    isSelected(p) {
      return (
        p.PurchaseRequestID ==
        this.$store.state.requester.selectedMainTable
          .PurchaseRequestID
      );
    },
    selectMe(spr) {
      this.$store.commit("requester/updateSelectedMainTable", spr);
      this.$store.dispatch("requester/searchDetail");
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
      this.$store.dispatch("requester/search");
      this.$store.commit("requester/updateSelectedMainTable", {});
      this.$store.commit("requester/updateDetailTable", []);
    },
    openDialogRequest() {
      this.$store.dispatch("requester/getRegional");
      this.$store.dispatch("requester/getVendorForm");
      this.$store.dispatch("requester/getItemTypeForm");
      this.$store.commit(
        "requester/updateXDateUse",
        moment(new Date()).format("YYYY-MM-DD")
      );
      this.$store.commit("requester/updateXBranch", "");
      this.$store.commit("requester/updateXDescription", "");
      this.$store.commit("requester/updateAct", "new");
      this.$store.commit("requester/updateDialogRequest", true);
    },
    updateRequest(val) {
      this.$store.dispatch("requester/branch");
      this.$store.commit(
        "requester/updateXDateUse",
        val.PurchaseRequestDirectDateUse
      );
      this.$store.commit(
        "requester/updateXBranch",
        val.PurchaseRequestDirectM_BranchCode
      );
      this.$store.commit(
        "requester/updateXDescription",
        val.PurchaseRequestDirectDescription
      );
      this.$store.commit("requester/updateXPRID", val.PurchaseRequestDirectID);
      this.$store.commit("requester/updateAct", "edit");
      this.$store.commit("requester/updateDialogRequest", true);
    },
    deleteRequest(data) {
      this.xPRID = data.PurchaseRequestDirectID;
      this.xNumber = data.PurchaseRequestDirectNumber;

      this.msgAlertDeleteRequest = `Yakin, mau hapus Purchase Request [${data.PurchaseRequestDirectNumber}] ?`;
      this.dialogAlertDeleteRequest = true;
    },
    closeAndDeleteRequest() {
      this.$store.dispatch("requester/deleteRequest", {
        PRID: this.xPRID,
        PRNumber: this.xNumber,
      });
      this.dialogAlertDeleteRequest = false;
    },
    openDialogDetail() {
      this.$store.commit("requester/updateXDescriptionDetail", "");
      this.$store.commit("requester/updateXAmountRequest", 0);
      this.$store.commit("requester/updateXEstimationPrice", 0);
      this.$store.commit("requester/updateAct", "new");
      this.$store.commit("requester/updateDialogDetail", true);
    },
    updateDetail(val) {
      this.$store.commit(
        "requester/updateXDescriptionDetail",
        val.PurchaseRequestDirectDescription
      );
      this.$store.commit(
        "requester/updateXAmountRequest",
        val.PurchaseRequestDirectDetailAmountRequest
      );
      this.$store.commit(
        "requester/updateXEstimationPrice",
        val.PurchaseRequestDirectDetailEstimationPrice
      );
      this.$store.commit("requester/updateSelectedDetailTable", val);
      this.$store.commit("requester/updateAct", "edit");
      this.$store.commit("requester/updateDialogDetail", true);
    },
    deleteDetail(data) {
      this.xPRDID = data.PurchaseRequestDirectDetailID;

      this.msgAlertDeleteRequest = `Yakin, mau hapus Request [${data.PurchaseRequestDirectDescription}] ?`;
      this.dialogAlertDeleteDetail = true;
    },
    closeAndDeleteDetail() {
      this.$store.dispatch("requester/deleteDetail", {
        PRDID: this.xPRDID,
        PRDDescriptionDetail: this.xDescriptionDetail,
        PRID: this.selectedMainTable.PurchaseRequestDirectID,
      });
      this.dialogAlertDeleteDetail = false;
    },
    orderRequest(id, status) {
      if (status === "Draft") {
        this.$store.dispatch("requester/orderRequest", {
          PRID: id,
        });
      }
    },
    thrSearchVendor: _.debounce(function () {
        this.$store.dispatch("requester/getVendor", this.searchVendor)
    }, 1000),
    thrSearchItemType: _.debounce(function () {
        this.$store.dispatch("requester/getItemType", this.searchItemType)
    }, 1000),
    thrSearchDate: _.debounce(function () {
        this.$store.dispatch("requester/search")
    }, 1000),
    thrSearch: _.debounce(function () {
        this.$store.dispatch("requester/search")
    }, 1000),
  },

  watch: {
    searchVendor(val, old) {
        if (val == old) return;
        if (!val) return;
        if (val.length < 1) return;
        if (this.$store.state.requester.loadingAutocomplete == 1) return;
        this.thrSearchVendor();
    },
    searchItemType(val, old) {
        if (val == old) return;
        if (!val) return;
        if (val.length < 1) return;
        if (this.$store.state.requester.loadingAutocomplete == 1) return;
        this.thrSearchItemType();
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
