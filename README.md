# External Blog extension for NEOS CMS

This package adds a new content type which shows a list of blog posts from an external blog. 

## Installation

```shell
composer req ujamii/externalblog
```

## Usage

The package provides a new content node type "External Blog Post List". When you add this
to your site, you have to provide a feed url (Atom or RSS) and you can set the amount of items
to show.

## TODOs

- feature: multi language
- feature: link editor for external blog posts (search by title, add url)
- feature: Command Controller for continuous import of external blog posts as Document nodes
  - make those posts searchable in the NEOS website

#### Templates

The fusion views are pretty basic, so you will probably want to overwrite it:

```neosfusion
prototype(Ujamii.ExternalBlog:Component.Molecule.PostList) < prototype(Neos.Fusion:Component) {

    # note about images: props.post.firstMediaUrl will contain the absolute URL to an external image.
    # if you prefer to store this locally, use this eel helper to get as asset:
    # imageResource = ${Ujamii.ExternalBlog.importRemoteImage(this.post.firstMediaUrl, "externalblog")}
    # you can also pass the target collection name as second argument. "imported" is default
    # if you import it as NEOS asset, you can apply resize, crop and so on. 
    
    renderer = 'Whatever you like'
}
```

If you want to adjust the rendering of the single content elements, just overwrite them as you like.
All the rendering is done with the files located in `Resources/Private/Fusion/Component/`.

## License and Contribution

[GPLv3](LICENSE)

As this is OpenSource, you are very welcome to contribute by reporting bugs, improve the code, write tests or
whatever you are able to do to improve the project.

If you want to do me a favour, buy me something from my [Amazon wishlist](https://www.amazon.de/registry/wishlist/2C7LSRMLEAD4F).
