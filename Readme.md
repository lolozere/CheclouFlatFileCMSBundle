# Checlou Flat File CMS Bundle for Symfony App

Symfony Bundle to create a CMS with flat files into a symfony app.

## Purpose

Provide a solution to a CMS feature in an existing symfony app.

- Content in markdown files

## Fast installation

Requirements : install a basic symfony app with composer.

```bash
symfony new my_project_directory --version="6.3.*"
composer require checlou/flat-file-cms-bundle
```

Set the configuration in `config/packages/checlou_flat_file_cms.yaml` :

```yaml
checlou_flat_file_cms:
  content_path: '%kernel.project_dir%/var/cms'
```

Copy files from `Tests/Functional/app/Website/cms` to `var/cms` to a basic page dataset.

## Theming

The default theming is a basic bootstrap 4 theme. You can override it by overriding the twig templates.

- [ ] Write documentation to explain how to override twig templates

## Content example

### File

Name of the file : `a-page.md`

```markdown
---
title: Page title
type: page
slug: a-page.html
---

Summary

===

Main content
```

Two differents types of content :

- page : a page with a title, a summary and a main content
- post : a post with a title, a summary and a main content

Default values :

- type : page
- slug : filename with `.html` extension and path as prefix
- title : filename without extension

### Organized by folders

```
content/
├── a-page.md
├── index.md
├── blog/
│   ├── a-post.md
│   └── another-post.md
│   └── a-category/
│       └── a-post.md
```

The index.md file is the content used to display the home page of the CMS. You can use this kind of file to create a page with a slug like 

- `/home` with an `index.md` file in the folder `home/`

## Others solutions to manage flat files

### Pico CMS or Grav CMS

Full app to manage a website with flat files. But you can not use it to add a CMS feature to an existing app.
Use Symfony components.

- https://symfony.com/projects/grav
- https://symfony.com/projects/pico

### Symfony Bundles

- https://github.com/maschmann/MarkdownContentBundle : too old (last commit in 2015)
- 

### Twig markdown to html

Extension to convert markdown to html in twig templates. We do not use it. Should we use it ?

https://twig.symfony.com/doc/3.x/filters/markdown_to_html.html

### Ideas

- https://coconnell.dev/posts/2023-10-08-creating-a-markdown-blog-with-symfony

## Todo

- More tests of Page and Pages class and Twig extension
- Shortcode system
- Inject global vars to use in markdown : by configuration and by event
- Content cache to rebuild without parsing files
- Configure the CMS to say that pages are isolated (no parent) even if it's in a subfolder
  - Usecase : a specific folder with isolated pages
- Transform link to markdown file to the url of the page
  - Usecase : use autocompletion in markdown editor to link to another page
- Command to generate all the website as an artefact
  - Usecase : use Github Pages to host the website
- Use https://twig.symfony.com/doc/3.x/filters/markdown_to_html.html ?