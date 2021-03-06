<template>
	<div id="content" class="app-shoppinglist">
		<AppNavigation>
			<AppNavigationNew v-if="!loading"
				:text="t('shoppinglist', 'New list')"
				:disabled="false"
				button-id="new-shoppinglist-button"
				button-class="icon-add"
				@click="newList" />
			<ul>
				<AppNavigationItem v-for="list in lists"
					:key="list.id"
					:title="list.title ? list.title : t('shoppinglist', 'New list')"
					:class="{active: currentList && currentList.id === list.id}"
					@click="openList(list)">
					<template slot="icon">
						<div class="container0">
							<ColorPicker v-model="list.color" @input="saveList(list)">
								<font-awesome-icon icon="circle" size="2x" :style="{color: list.color}" />
							</ColorPicker>
						</div>
					</template>
					<template slot="actions">
						<ActionButton
							icon="icon-delete"
							@click="deleteList(list)">
							{{ t('shoppinglist', 'Delete list') }}
						</ActionButton>
					</template>
				</AppNavigationItem>
			</ul>
		</AppNavigation>
		<AppContent>
			<div v-if="currentList" class="shopping_list_frame">
				<div
					class="title_div"
					:style="{'background-color': 'color' in currentList ? currentList.color : 'white', height: '50px', 'text-align': 'center', 'display': 'inline-block', 'vertical-align': 'middle'}">
					<input v-model="currentList.title"
						type="text"
						class="title"
						:style="{'border': 'none', 'background-color': currentList.color}"
						@change="saveList()">
				</div>

				<input ref="title"
					v-model="newItemText"
					type="text"
					:disabled="updating"
					class="newItemInput"
					:placeholder="t('shoppinglist', 'Add an Item')"
					@input="suggestItem"
					@keydown="$event.key=='Enter'? addNewItem(suggestedItems[0]) : null">

				<table>
					<tr v-for="(item, index) in suggestedItems"
						:key="'suggested'+index">
						<td>{{ item.name }}</td>
						<td><input v-model="item.amount" :style="{color: 'gray', 'text-align': 'right', 'border': 'none'}"></td>
						<td>
							<span @click="addNewItem(item)">
								<font-awesome-icon icon="plus" />
							</span>
						</td>
					</tr>
				</table>
				<draggable
					v-model="activeItems"
					class="list-group"
					tag="ul">
					@change="updateList(currentList)"
					<li v-for="item in activeItems"
						:key="item.id"
						:class="['item-wrapper__item', 'drag-el', 'list-group-item' ]"
						@contextmenu.prevent.stop="handleContextMenu($event, item)">
						<td>
							<input v-model="item.name"
								type="text"
								:style="{'border': 'none'}"
								@change="saveList()">
						</td>
						<td><input v-model="item.amount" :style="{color: 'gray', 'text-align': 'right', 'border': 'none'}" @change="saveList()"></td>
						<td>
							<span @click="checkboxChange(item, false)">
								<font-awesome-icon :icon="['far', 'square']" />
							</span>
						</td>
					</li>
				</draggable>
				{{ t('shoppinglist', 'recent') }}
				<table>
					<tr v-for="item in inactiveItems"
						:key="item.id"
						:class="['item-wrapper__item', 'drag-el']"
						@contextmenu.prevent.stop="handleContextMenu($event, item)">
						<td colspan="2" :style="{color: 'gray', 'min-width':'200px'}">
							<input v-model="item.name"
								type="text"
								:style="{color: 'gray', 'border': 'none'}"
								@change="saveList()">
						</td>
						<td>
							<span @click="checkboxChange(item, true)">
								<font-awesome-icon icon="plus" class="drag-icon" :style="{'color': currentList.color}" />
							</span>
						</td>
					</tr>
				</table>
			</div>
			<div v-else id="emptycontent">
				<div class="icon-file" />
				<h2>{{ t('shoppinglist', 'Open or create a list to get started') }}</h2>
			</div>
		</AppContent>
		<VueSimpleContextMenu
			:ref="'vueSimpleContextMenu'"
			:element-id="'contextMenu'"
			:options="[{name: t('shoppinglist', 'delete')}]"
			@option-clicked="deleteItem($event)" />
	</div>
</template>
<script src="https://kit.fontawesome.com/c93704883a.js" crossorigin="anonymous"></script>
<script>
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import AppContent from '@nextcloud/vue/dist/Components/AppContent'
import AppNavigation from '@nextcloud/vue/dist/Components/AppNavigation'
import AppNavigationItem from '@nextcloud/vue/dist/Components/AppNavigationItem'
import AppNavigationNew from '@nextcloud/vue/dist/Components/AppNavigationNew'
import ColorPicker from '@nextcloud/vue/dist/Components/ColorPicker'
import Actions from '@nextcloud/vue/dist/Components/Actions'
import '@nextcloud/dialogs/styles/toast.scss'
import { generateUrl } from '@nextcloud/router'
import { showError, showSuccess } from '@nextcloud/dialogs'
import axios from '@nextcloud/axios'

import { library } from '@fortawesome/fontawesome-svg-core'
import { faSpinner, faPlus, faCircle, faBars } from '@fortawesome/free-solid-svg-icons'
import { faSquare } from '@fortawesome/free-regular-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { v4 as uuidv4 } from 'uuid';

import 'vue-simple-context-menu/dist/vue-simple-context-menu.css'
import draggable from 'vuedraggable'
import VueSimpleContextMenu from 'vue-simple-context-menu'

library.add(faSpinner, faPlus, faSquare, faCircle, faBars)
export default {
	name: 'App',
	components: {
		ActionButton,
		AppContent,
		AppNavigation,
		AppNavigationItem,
		AppNavigationNew,
		FontAwesomeIcon,
		ColorPicker,
		Actions,
		draggable,
		VueSimpleContextMenu,
	},
	data() {
		return {
			lists: [],
			currentList: null,
			updating: false,
			loading: true,
			checked: [],
			newItemText: "",
			activeItems: [],
			inactiveItems: [],
			suggestedItems: [],
		}
	},
	computed: {

		/**
		 * Returns true if a list is selected and its title is not empty
		 * @returns {Boolean}
		 */
		savePossible() {
			return this.currentList && this.currentList.title !== ''
		},
	},
	/**
	 * Fetch list of lists when the componenat is loaded
	 */
	async mounted() {
		try {
			const response = await axios.get(generateUrl('/apps/shoppinglist/lists'))
			this.lists = response.data
			console.warn("received:")
			console.log(this.lists)

		} catch (e) {
			console.error(e)
			showError(t('shoppinglist', 'Could not fetch lists'))
		}
		this.loading = false
		console.log("What is going on")
	},

	methods: {
		/**
		 * Create a new list and focus the list content field automatically
		 * @param {Object} list List object
		 */
		openList(list) {
			if (this.updating) {
				return
			}
			this.currentList = list
			this.activeItems = this.currentList.items.filter(item => item.active == true)
			this.inactiveItems = this.currentList.items.filter(item => item.active == false)
		},
		checkboxChange(item, newState){
				let oldList = newState ? this.inactiveItems : this.activeItems
				let newList = newState ? this.activeItems : this.inactiveItems
				oldList.splice(oldList.indexOf(item), 1)
				newList.push(item)
				item.active = newState
				item.editedDate = new Date().toISOString()
				this.$forceUpdate()
				this.saveList()
		},
		handleContextMenu(event, item){
			this.$refs.vueSimpleContextMenu.showMenu(event, item)
		},
		suggestItem(){
			if(this.newItemText && this.newItemText.trim() !== ''){
				let item = {}
				let units = ["k?g", "m?l"] //these are the units supported by default
				//When a user adds an item with a custom unit of measurement, it becomes part of the available units
				for (let existingItem of this.currentList.items){
					let unit = existingItem.amount.replace(new RegExp("[0-9]", "g"), "").trim()
					units.push(unit)
				}
				let regexString = "[0-9]+\\s?("+units.join("|")+")?"
				let regex = new RegExp(regexString, "gi") //global match, ignoring case
				let match = this.newItemText.match(regex)
				item.amount= match ? match[0] : ""
				item.name = this.newItemText.replace(item.amount, "")
				this.suggestedItems = [item]
				for (let existingItem of this.currentList.items){
					if (existingItem.name.includes(this.newItemText)){
						this.suggestedItems.push(Object.assign({}, existingItem))
					}
				}
			}
		},
		addNewItem(item){
			if(item && item.name && item.name.trim() !== ''){
			item.active = true
			item.createdDate = new Date().toISOString()
			item.editedDate = new Date().toISOString()
			item.id = uuidv4() //New items get a uuid in order to identify them
			this.suggestedItems = []
			this.newItemText = null
			this.activeItems.push(item)
			this.saveList()

			}
		},
		deleteItem(event){
			try {
				let list = event.item.active ? this.activeItems : this.inactiveItems
				list.splice(list.indexOf(event.item), 1)
				this.saveList()
				showSuccess(t('shoppinglist', 'Item deleted'))
			} catch (e) {
				console.error(e)
				showError(t('shoppinglist', 'Could not create the list'))
			}
		},
		/**
		 * Action tiggered when clicking the save button
		 * create a new list or save
		 */
		saveList(list=this.currentList) {
			if (list.id === -1) {
				this.createList(list)
			} else {
				this.updateList(list)
			}
		},
		/**
		 * Create a new list and focus the list content field automatically
		 * The list is not yet saved, therefore an id of -1 is used until it
		 * has been persisted in the backend
		 */
		async newList() {

			let list = {
				id: uuidv4(),
				author: '',
				title: t('shoppinglist', 'New list'),
				color: '#0082c9',
				items: [],
				createdDate: new Date().toISOString()

			}
			this.updating = true
			console.log(list)
			try {
				const response = await axios.post(generateUrl('/apps/shoppinglist/lists'), {"list": list})
				this.lists.push(list)
				this.openList(list)
			} catch (e) {
				console.error(e)
				showError(t('shoppinglist', 'Could not create the list'))
			}
			this.updating = false

		},
		/**
		 * Create a new list by sending the information to the server
		 * @param {Object} list List object
		 */
		async createList(list) {
			this.updating = true
			try {
				const response = await axios.post(generateUrl('/apps/shoppinglist/lists'), list)
				const index = this.lists.findIndex((match) => match.id === this.currentListId)
				this.$set(this.lists, index, response.data)
				this.currentListId = response.data.id
			} catch (e) {
				console.error(e)
				showError(t('shoppinglist', 'Could not create the list'))
			}
			this.updating = false
		},
		/**
		 * Update an existing list on the server
		 *
		 * @param {Object} list List object
		 */
		async updateList(list) {
			this.updating = true
			this.currentList.items = [...this.activeItems, ...this.inactiveItems]
			list["editedDate"] = new Date().toISOString()
			axios.put(generateUrl(`/apps/shoppinglist/lists/${list.id}`), {"list": list})
			.then((res) => console.log(res))
			.finally(()=>this.updating = false)
			.catch(e => {
				console.error(e)
				showError(t('shoppinglist', 'Could not update the list'))
				this.updating = false
			})

		},
		/**
		 * Delete a list, remove it from the frontend and show a hint
		 *
		 * @param {Object} list List object
		 */
		deleteList(list) {
			console.log("delete")
			console.log(list)
			axios.delete(generateUrl(`/apps/shoppinglist/lists/${list.id}`))
			.then((res) => {
				console.log(res)
				this.lists.splice(this.lists.indexOf(list), 1)
				if (this.currentList && this.currentList.id === list.id) {
					this.currentList = null
				}
				showSuccess(t('shoppinglist', 'List deleted'))
			})
			.catch((e)=>{
				console.error(e)
			showError(t('shoppinglist', 'Could not delete the list'))

			})

		},
		convertHexToRGBA (hexCode, opacity){
			let hex = hexCode.replace('#', '')

			if (hex.length === 3) {
				hex = `${hex[0]}${hex[0]}${hex[1]}${hex[1]}${hex[2]}${hex[2]}`
			}

			const r = parseInt(hex.substring(0, 2), 16)
			const g = parseInt(hex.substring(2, 4), 16)
			const b = parseInt(hex.substring(4, 6), 16)

			return `rgba(${r},${g},${b},${opacity / 100})`
		},
	},
}
</script>
<style scoped>
	.app-content {
		padding-top: 40px;

	}

	.shopping_list_frame{
		max-width: 400px;
		width: 400px;
		display: inline-block;
		text-align: center;
		vertical-align: middle;
	}

	#app-content > div {
		width: 100%;
		height: 100%;
		padding: 20px;
		display: flex;
		flex-direction: column;
		flex-grow: 1;
	}

	input[type='text'] {
		width: 80%;
	}

	textarea {
		flex-grow: 1;
		width: 100%;
	}

	table {
		border-collapse: collapse;
		width: 100%;
	}

	td    {padding-right: 6px;}

	#contextMenu{
		border: 1px;
	}

	.title {
		font    : sans-serif;
		font-size: 20px !important;
		text-align: center;
		border  : none;
		padding : 0 10px;
		margin  : 0;
		width   : 240px;
		background: none;
	}

	.title_div{
		width: 100%;
	}

	td.spacer{
		padding-top: 20px;
		padding-bottom: 10px;
	}

	.app-navigation-entry-link * {
		vertical-align: middle;
		text-align: center;
	}
</style>
