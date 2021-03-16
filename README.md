# Nextcloud Shopping List App

This is a simple Shopping List app for Nextcloud.

It is based on the Nextcloud Notes [tutorial app](https://docs.nextcloud.com/server/latest/developer_manual/app_development/tutorial.html)

# Features

## Basic functionality

I Implemented some Basic features

You can:

- Create, rename and delete Shopping Lists
- Change the Color of a list by clicking on the colored circle in front of its name

- Add Items to a list. The quantity will be recognized automatically. The Logic is as follows:
  - If the typed text includes a number followed a known unit (default is "g", "kg", "l" and "ml", non case sensitive), it will add this as the quantity
  - You can manually edit quanitities. If you have manually added a custom unit (e.g. ounces), the app adds "ounces" to the known units. So when you type "2 ounces" in the future, the app will add this as your quantity
- Check off items from the list. They will appear in the "recent" Tab
- Delete items by right clicking

## Sharing Shoppinglists

Each Shopping list is stored in a JSON file in the "Shoppinglists" Folder.
By sharing the file with another person, you can both access and edit the same list.

## Android

There is no Android Client yet. I havent done any Android programming yet. Feel free to implement it.
Ill probably look into it when I can spare some time.

# Install

To install it change into your Nextcloud's apps directory:

    `cd nextcloud/apps`

Then run:
    `git clone https://github.com/Tom-Finke/shoppinglist-nextcloud.git shoppinglist`
> The directory you clone the repository to has to be called "shoppinglist", or enabling the app won't work

Then install the dependencies using:
    `make composer`

Install JS Dependencies and build js from Vue
    `npm install`
    `make build-js`
    
Note: You might have to install some PHP Dependencies. Just lool at the Make output if it fails. For me it was php dom and php mbstring

# Contribute

You are welcome to contribute to this project.
I have a couple of things planned.
If you have an Idea for a feature or a bug report, feel free to open an iusse

## Get started

This is my first Nextcloud App. I found that it's not trivial to understand how everything works wit Nextcloud Apps.
If you have any questions, feel free to ask, but I can't promies that I'll be able to give you a sattisfying answer.

It really helped me to get started with the Nextcloud [tutorial app](https://docs.nextcloud.com/server/latest/developer_manual/app_development/tutorial.html) and build my way up from there.
So I can really recommend having a look at that.

Also, for reading and saving JSON Files, I looked into the [Nextcloud Cookbook](https://github.com/nextcloud/cookbook.git)Project.

Through Cookbook, i stumbled upon the Nextcloud [Docker Debug Repo](https://github.com/christianlupus/nextcloud-docker-debug), which I can highly recommend. 

## TODS

- Ability to sort list entries into an arbitrary order
- Better Sharing, maybe a sharing Icon for each list
- Add categories. Every Item can have a category
- Add support for other languages
- Add Settings menu, for setting:
  - The Lists Source Folder
  - Default Color
  - Maybe default List Name
- Create an Android Client
- Filename in which a list is stored should be similar to the lists name (as stored in the json)

## Frontend development

If you want to contribute to this app, you can get started by tinkering with the [Vue.js](https://vuejs.org/) Frontend. To build the frontend code after doing changes to its source in `src/` requires to have Node and npm installed.

- 👩‍💻 Run `make dev-setup` to install the frontend dependencies
- 🏗 To build the Javascript whenever you make changes, run `make build-js`

To continuously run the build when editing source files you can make use of the `make watch-js` command.

## Backend Development

If you make changes to the Backend PHP code, no restart or `make` should be necessary. The changes will be reflected instantaneously
