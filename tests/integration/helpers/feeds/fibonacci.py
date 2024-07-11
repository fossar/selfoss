import datetime
from xml.dom import getDOMImplementation


def numbers(n: int):
    """
    Generates first *n* fibonacci numbers.
    """
    numbers = []
    a = 1
    b = 2
    for i in range(n):
        numbers.append((i, a))
        a, b = b, a + b
        yield i, a


def numbers_feed(n: int) -> str:
    """
    Generates a RSS feed containing first *n* fibonacci numbers.
    """
    doc = getDOMImplementation().createDocument(None, "rss", None)
    root = doc.documentElement
    root.setAttribute("version", "2.0")

    channel = doc.createElement("channel")
    root.appendChild(channel)

    title = doc.createElement("title")
    channel.appendChild(title)
    title.appendChild(doc.createTextNode(f"{n} numbers"))

    od = datetime.datetime.now() - datetime.timedelta(minutes=n)

    for k, a in reversed(list(numbers(n))):
        item = doc.createElement("item")
        channel.appendChild(item)

        item_title = doc.createElement("title")
        item.appendChild(item_title)
        item_title.appendChild(doc.createTextNode(f"{a}"))

        item_guid = doc.createElement("guid")
        item.appendChild(item_guid)
        item_guid.setAttribute("isPermaLink", "true")
        item_guid.appendChild(doc.createTextNode(f"https://en.wikipedia.org/wiki/{a}"))

        d = od + datetime.timedelta(minutes=k)
        item_pubdate = doc.createElement("pubDate")
        item.appendChild(item_pubdate)
        item_pubdate.appendChild(
            doc.createTextNode(d.strftime("%a, %d %b %Y %H:%M:%S %z"))
        )

    return doc.toprettyxml(indent=" " * 4)
