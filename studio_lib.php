<?php
/* =====================================================================
 *  studio_lib.php — the studio mood photos (5 Nov 2026)
 *
 *  Photos of an artist's studio that give the site its atmosphere. They
 *  are MOOD photos only: never shown as artworks for sale, never as the
 *  work of an Oweili artist, never in the Gallery, the sitemap or as an
 *  artwork's share picture.
 *
 *  Files: assets/img/studio/<key>-<tall|wide>-<width>.<avif|webp|jpg>
 *  (made once from the owner's originals: cropped for phone ("tall") and
 *  computer ("wide"), compressed, hidden data removed). Phones get the
 *  "tall" crop, screens over 640px the "wide" one; each browser takes the
 *  lightest format it can show (AVIF, then WebP, then JPEG).
 *
 *    owStudioPhoto($key, $eager)   the <picture> (lazy unless $eager)
 *    owStudioHas($key)             true when the files exist
 *    owStudioBackdrop(...$keys)    the photo as the background of the WHOLE
 *                                  page (the first key whose files exist):
 *                                  a still layer behind everything with a soft
 *                                  veil (light and dark mode). It is a fixed
 *                                  layer, not background-attachment: fixed,
 *                                  so it also stays still on iPhone
 *
 *  Where they are used: homepage (behind the video), Join as an artist,
 *  Gifts, Explore Art Styles, the 404 page and the empty Gallery; "hand on the
 *  palette" → Commissions, "woman looking at framed paintings" → Art Events,
 *  Artist Spotlights, Art Education, articles and Institutes, "studio table
 *  with brushes" → Support.
 * ===================================================================== */
require_once __DIR__ . '/ow_paths.php';

const OW_STUDIO = [
    // key => [phone widths, computer widths, phone w/h, computer w/h, alt EN, alt AR]
    // home-painting: the homepage photo behind the video. The others fill the WHOLE page
    // behind everything (owStudioBackdrop): 9:16 for phones, 16:9 for computers.
    'home-painting' => [[640, 960], [1333], [2, 3], [16, 9],
        'An artist painting a sunny landscape on an easel outdoors',
        'فنانة ترسم منظراً طبيعياً مشمساً على حامل لوحات في الهواء الطلق'],
    'brushes-jars'  => [[480, 720], [1280], [9, 16], [16, 9],
        'Well-used paint brushes standing in glass jars in a studio',
        'فُرَش رسم مستعملة في برطمانات زجاجية داخل مرسم'],
    'pink-palette'  => [[480, 632], [1280, 1500], [9, 16], [16, 9],
        'A wooden palette with pink and orange paint beside paint brushes',
        'لوحة ألوان خشبية بألوان وردية وبرتقالية بجانب فُرَش الرسم'],
    'paint-buckets' => [[480, 632], [1280, 1920], [9, 16], [16, 9],
        'A hand dipping a brush into buckets of colourful paint, seen from above',
        'يد تغمس فرشاة في علب ألوان زاهية، من الأعلى'],
    'paint-cloth'   => [[480, 720], [1280], [9, 16], [16, 9],
        'A paint-splattered cloth with bowls of colour and brushes',
        'قماش مرشوش بالألوان مع أوعية الألوان والفُرَش'],
    // the owner's last 3 photos (5 Nov 2026); palette-hand is cut so the painting of roses never shows
    'palette-hand'  => [[480, 720], [1100], [9, 16], [16, 9],
        'A hand holding a paint palette in a studio',
        'يد تمسك لوحة الألوان داخل مرسم'],
    'gallery-visit' => [[480, 720], [1280, 1920], [9, 16], [16, 9],
        'A woman looking at framed paintings in a gallery',
        'امرأة تتأمل لوحات مؤطرة في معرض'],
    'studio-table'  => [[480, 720], [1280, 1920], [9, 16], [16, 9],
        'A studio table with brushes and paints',
        'طاولة مرسم عليها فُرَش وألوان'],
];

/* A tiny blurred copy of each photo (about 0.7 KB, made from the photo itself, 6 Nov 2026).
   It is drawn the moment the page opens, so the page never shows a plain white or navy
   frame while the real photo is still loading: no flash between pages. */
const OW_STUDIO_TINY = [
    'brushes-jars-tall' => 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA0JCgsKCA0LCgsODg0PEyAVExISEyccHhcgLikxMC4pLSwzOko+MzZGNywtQFdBRkxOUlNSMj5aYVpQYEpRUk//2wBDAQ4ODhMREyYVFSZPNS01T09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0//wAARCAAgABIDASIAAhEBAxEB/8QAGgAAAgIDAAAAAAAAAAAAAAAAAAQDBQEGB//EACwQAAEDAwMACAcAAAAAAAAAAAEAAgMEESEFEjEGExQiQUJRYVKBkbHB0eH/xAAYAQADAQEAAAAAAAAAAAAAAAAAAQMCBP/EABcRAQEBAQAAAAAAAAAAAAAAAAABETH/2gAMAwEAAhEDEQA/ANtqx3GKIAFtlTU+uB4ZT1IJcCGtkHmFskp6Csjm28tc7gH7flc2qZhja34QhY3eyEw54yDUyypdK5zdsWDcHzD+/RO6PJ1GnT1Es26UYax+bWN+PW9ld0mhiebUXioETJiWgkeLm5x88JWh6PSUfbJpYpJg2PY15sQ7IuQOc28U7ZYOVGdfuSTVSMJ5bsGPZCkLdNvlpB9LD9IWMU1//9k=',
    'brushes-jars-wide' => 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA0JCgsKCA0LCgsODg0PEyAVExISEyccHhcgLikxMC4pLSwzOko+MzZGNywtQFdBRkxOUlNSMj5aYVpQYEpRUk//2wBDAQ4ODhMREyYVFSZPNS01T09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0//wAARCAASACADASIAAhEBAxEB/8QAGAAAAwEBAAAAAAAAAAAAAAAAAAQGAgP/xAAqEAACAQMDAQYHAAAAAAAAAAABAgMAESEEBRIxBhMyUbHBIiMzQYGy8P/EABYBAQEBAAAAAAAAAAAAAAAAAAMCAf/EABkRAAIDAQAAAAAAAAAAAAAAAAABAhESMf/aAAwDAQACEQMRAD8AXado5op4SVEfO182uBnrTcW6zPp072cRysyuM9FIsAb1GSS6t41+rxJDjqQbeX99qZ3ZNQmtPN2sVQpyzbAIFBgXReRbkhkszAqQCTgCPHQ+efWnXvxxUxtkB0WzvKzIXdhzL/EAFUe/tWZt17/T/MmkKyoImF7cWxfP4qW6NUbNrFGdPpyY0OI+qikO0qgayawA8H7sPTFFFWiTpKzHaXUsePDpfHiFIOAFmAAt3kZtRRRvokeH/9k=',
    'pink-palette-tall' => 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA0JCgsKCA0LCgsODg0PEyAVExISEyccHhcgLikxMC4pLSwzOko+MzZGNywtQFdBRkxOUlNSMj5aYVpQYEpRUk//2wBDAQ4ODhMREyYVFSZPNS01T09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0//wAARCAAgABIDASIAAhEBAxEB/8QAGAAAAwEBAAAAAAAAAAAAAAAAAAUGAQT/xAArEAACAQMCAwYHAAAAAAAAAAABAgMABBEFEhNBUSExUmFxgQYVIjNCQ8H/xAAYAQEAAwEAAAAAAAAAAAAAAAADAAEEBf/EAB4RAAICAgIDAAAAAAAAAAAAAAACARETURIhAzFS/9oADAMBAAIRAxEAPwDdbu3kmQiTMZHao7MGlZuooY98j7VXnXcwGDHMVwe89aTXsUUN3Alw7SWZfDKjbSw8OaBJaVpoo6zeTEtLA+W+hZAyyIwIyDuAzRU++h6cXYx392iE/SptixUdMg4PtRSd/RizvqSutbG2u8bJws23cvMSL3bsdeRHKl178NwXYF6r8cQ5VohkevvWzl4nF4E4MiNifH6n8Q8jzrstdUhiuTdgbA+Fu4ua9HHl/KnK5oNnaU79E78imP25yU/E8QDs9KKs20LTJmMvAibed2euaKvhGg8r7P/Z',
    'pink-palette-wide' => 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA0JCgsKCA0LCgsODg0PEyAVExISEyccHhcgLikxMC4pLSwzOko+MzZGNywtQFdBRkxOUlNSMj5aYVpQYEpRUk//2wBDAQ4ODhMREyYVFSZPNS01T09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0//wAARCAASACADASIAAhEBAxEB/8QAGAAAAwEBAAAAAAAAAAAAAAAAAAUGAwT/xAApEAACAQMDAgYCAwAAAAAAAAABAgMABBEFEiFBURMiMmFxkQYUQsHR/8QAGAEAAwEBAAAAAAAAAAAAAAAAAAEDAgT/xAAeEQACAQQDAQAAAAAAAAAAAAABAgADESEyBBIiQf/aAAwDAQACEQMRAD8Asgam9duC91NaMRggYz0HQ0w0/Xba/u3togd6luQcjaOp7VwXzxanchobmPw2A2ORx99s8ex+axUXuMG0rx2FN7tJmZ7mORYIkzPKwWMEcE/J4rrW/hjszcyER3QYxyWzeoOO/YVvq+hXkwGyZQsDZkA8zAY4pStp+xM63bNHdRLtRh6WA5Ge49v64oGDcy713LeTHX5Wqx+M0ahCRACVGOrVnGTJo0LSeYmcgluer/4Pqiigzl+COtNJa/tSxJL2bbif5YIxmoxONemUcKJ5RjpwpNFFJtY12n//2Q==',
    'paint-buckets-tall' => 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA0JCgsKCA0LCgsODg0PEyAVExISEyccHhcgLikxMC4pLSwzOko+MzZGNywtQFdBRkxOUlNSMj5aYVpQYEpRUk//2wBDAQ4ODhMREyYVFSZPNS01T09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0//wAARCAAgABIDASIAAhEBAxEB/8QAGgAAAgIDAAAAAAAAAAAAAAAAAAUBAwIEBv/EACkQAAIBAwMDAQkAAAAAAAAAAAECAwAEEQUSIQYTMUEUMlFxgZGhscH/xAAYAQADAQEAAAAAAAAAAAAAAAACAwQBBf/EAB4RAAEEAgMBAAAAAAAAAAAAAAEAAgMRBFEyQWGR/9oADAMBAAIRAxEAPwBFea2dSs3tWgSC3kIIKsWbg8ZzWrA0kUqs0b48oygkY9MGs9X04wyySW5bt7yNhTBB/tTbSHsbQSO1gkbvewfWhx3iLiEzLiEtBWspLEkRA58FlB/dFLJdBeeZ5lu49sjFhuVs4PPPFFNMl6UwgAHf1M5NKmXTxFMpXLbjMjbgrDgc/im1j0fevE0k11C0j4O0g8jzyfjVumWFxAJra6A9nbPJ8H5U1k6n0rTiIrqciUADaq7ift4+tY9ga6rtFDIXtsilz01pqyTyKElG1iMB+BzRTiXqJGlciGHBYkZPNFQmEeroDIOgv//Z',
    'paint-buckets-wide' => 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA0JCgsKCA0LCgsODg0PEyAVExISEyccHhcgLikxMC4pLSwzOko+MzZGNywtQFdBRkxOUlNSMj5aYVpQYEpRUk//2wBDAQ4ODhMREyYVFSZPNS01T09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0//wAARCAASACADASIAAhEBAxEB/8QAGQAAAgMBAAAAAAAAAAAAAAAAAAYCBAUD/8QAKBAAAgEDBAEDBAMAAAAAAAAAAQIDAAQRBRIhMUEUUXETFSJhI4GR/8QAFgEBAQEAAAAAAAAAAAAAAAAAAwIE/8QAIBEAAgEDBAMAAAAAAAAAAAAAAAECAzEyEhNBgSFxwf/aAAwDAQACEQMRAD8AzNV1uMXVrYXFoDKNu26RtrLzx84rlrEt9bwJE8iy2ki4O1R+R3eT807vHCs4tmtrdAoG0qwMgJ89dYxzSbNofpry606O+Z8NvVcHIBOePBNQ6er2hd1Qys/BRhvpNKlguIk9QQ/GRgcjHOO/anEK2q2TteNGF7hK8fTJ4x+z71TbQftKW80V0ZdzbW3Lgn4x4/VZKXsltqF2+nQLLCjMhjCkg+4GKtVJSk0+AnCNOKasMkrMbiJyxL7n/LPPVRADFpWAMgLDce/9oopeegXj2vgn21zPLqF+ss8jhJMKGcnaOevatXSiVgudpI/lbr+qKKzmk//Z',
    'paint-cloth-tall' => 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA0JCgsKCA0LCgsODg0PEyAVExISEyccHhcgLikxMC4pLSwzOko+MzZGNywtQFdBRkxOUlNSMj5aYVpQYEpRUk//2wBDAQ4ODhMREyYVFSZPNS01T09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0//wAARCAAgABIDASIAAhEBAxEB/8QAGQAAAwADAAAAAAAAAAAAAAAAAAUGAgME/8QAKhAAAQMDAwIEBwAAAAAAAAAAAgEDBAARIQUSMRNBIlFxkQYVMlJhgaH/xAAXAQADAQAAAAAAAAAAAAAAAAABAwQC/8QAIhEAAQMDAwUAAAAAAAAAAAAAAQACAxEhMQQSQRNRYZGh/9oADAMBAAIRAxEAPwB1rWqHHbJiGJFIQEcXfdARL2yqZ9u9SkuYUt4mgkEbo/WrZ3xfkb8ql/5V1Nix5TBJLHeAluwtrWW/b0qX0+HpzE0VfhoPXFen0iwXiuiWTN0Tm+FrHVbEb8+01kZkGMJCGpamyAtI5ALYm25xj3LbzxzRVavw/pxEpdCRlb4dWin1j8/Ejc7uuHX9VPT4fy5JUl99skJx8tqbrrfaX449qwgxlhz2ZRgiPPYQPsJewrhK06iiHKdecaiuL1VBqxqd1HlS7L2xREjSnmE1N5TJxHRJoL+EURbbv1nHpUmpje4N245V2leGFzSbqwCIJgJGTe4kuvrRSw3NVUyVuLEML+Eld5TzoogWU5rVf//Z',
    'paint-cloth-wide' => 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA0JCgsKCA0LCgsODg0PEyAVExISEyccHhcgLikxMC4pLSwzOko+MzZGNywtQFdBRkxOUlNSMj5aYVpQYEpRUk//2wBDAQ4ODhMREyYVFSZPNS01T09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0//wAARCAASACADASIAAhEBAxEB/8QAGQAAAwADAAAAAAAAAAAAAAAAAAQGAgMF/8QAKhAAAQMEAQIDCQAAAAAAAAAAAQIDEQAEBSESBhMxYbEUIjVBUXOBkcH/xAAWAQEBAQAAAAAAAAAAAAAAAAAFAQT/xAAiEQACAQIFBQAAAAAAAAAAAAAAAQIDEQQxNFGBEiEiMmH/2gAMAwEAAhEDEQA/AKwji2EtgqBIEyDMmDW4JEq/IMj9GlkDk4FSO7xIkGRA36xXAvbprB4xePdvr24fdSpXdBHNE7GzUSv2RTLKPtu5J1ftiLd1looQSkFIB2rR9aaw1+/e2CVcAlpuEKVMhShox9AalrJpWVsx3ipDTOm3CB70mYIA34+JOqpcKVqbatkIQkJlKy2Dw4j++VYZySrWb6nfLYRcIPD+Kt93H7FIS0yEgABWgKkupCVZq55GeKBE/LQoopKhmHy9xnpT4Afur9af6YJ7mT2dPtR5aoooWOrnyKS0q4P/2Q==',
    'palette-hand-tall' => 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA0JCgsKCA0LCgsODg0PEyAVExISEyccHhcgLikxMC4pLSwzOko+MzZGNywtQFdBRkxOUlNSMj5aYVpQYEpRUk//2wBDAQ4ODhMREyYVFSZPNS01T09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0//wAARCAAgABIDASIAAhEBAxEB/8QAGAABAQEBAQAAAAAAAAAAAAAABgAEAwX/xAAqEAACAQMDAwMDBQAAAAAAAAABAgQAAxEFEiExQVETImEGM2JxgZHR4f/EABUBAQEAAAAAAAAAAAAAAAAAAAEC/8QAHBEBAAICAwEAAAAAAAAAAAAAAQARAhIhMVHw/9oADAMBAAIRAxEAPwAfCebekM8OzckuDvKsu4HHwetJdOnfUmoMRdmW4SW8Eg2F3H9v9rwtE1iSGMW1Zjgsoy7WwcheoOfI71utXWE5pVyTbthm2kG4Qig9+eSMY6UXTExE4jQTDgZwT3PFVFmlWt52TnZc8MttsEeR8VU7vkdD0hPTkKyVbnb1PuwCKQC/DLFDHHqsmQN3OO3PYUcNu9DYsyMm1tjBh3HWt0K368WZqPqBTEKlt5+4rcBR+XH8Z8UZCwE6ZrNueT7JDIvZQ7YA8VVyGqx8DK3s/oP7qqKZVn1z/9k=',
    'palette-hand-wide' => 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA0JCgsKCA0LCgsODg0PEyAVExISEyccHhcgLikxMC4pLSwzOko+MzZGNywtQFdBRkxOUlNSMj5aYVpQYEpRUk//2wBDAQ4ODhMREyYVFSZPNS01T09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0//wAARCAASACADASIAAhEBAxEB/8QAGAAAAwEBAAAAAAAAAAAAAAAAAAUGBAH/xAAqEAACAQQAAwYHAAAAAAAAAAABAgMABBEhBRJBBhMUIjFRIzJhcoGh4f/EABYBAQEBAAAAAAAAAAAAAAAAAAIBAP/EAB0RAAIBBQEBAAAAAAAAAAAAAAACAQMREiEyUTH/2gAMAwEAAhEDEQA/AIyy4dc3VxCMle+xyMBkEGnd52Ws4URue8tyQWMcwBU4BJHMNDQpDYcbvrHuu5lJWPQVtjHt+zT5LzistpOwnlaK6UELKylFB3136ZFFsr6EmNt/TbwCWwtC9vC8Tudq6jBdfb8U2a7hXbEAfdUra2pin8TGglZnA8i+hI+XHt/Ka39s9kIPEmF5XB+Ekw8o+p6euKsv4aKfskBVQoC2Npy6ygzjrqiiq3MhTqDNcSOLWOMOwQkkqDo7PSu8AjR4OIc6K3LBkZGcHdFFFB1j/9k=',
    'gallery-visit-tall' => 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA0JCgsKCA0LCgsODg0PEyAVExISEyccHhcgLikxMC4pLSwzOko+MzZGNywtQFdBRkxOUlNSMj5aYVpQYEpRUk//2wBDAQ4ODhMREyYVFSZPNS01T09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0//wAARCAAgABIDASIAAhEBAxEB/8QAGAAAAwEBAAAAAAAAAAAAAAAAAAQFBgH/xAAtEAACAQMCAgcJAAAAAAAAAAABAgMABBEFEhMVBhQhIjFRU0JhcYGCkZLB8P/EABcBAQEBAQAAAAAAAAAAAAAAAAEDAAL/xAAcEQACAgIDAAAAAAAAAAAAAAAAAQIDEWESE0H/2gAMAwEAAhEDEQA/AMnBzGV9ouJh8WNNy22qoBi6lb6jVKae0s1hmixMSMSDcRtOB5j3N4V06vBqCrHBGqbgWDF/L5VHZVPwj8LV/Xl/KirccT8Nd9zBuwM99fH7UUZHGwudO61a3Zv42RUjLoUkydwBOeypfRzSYL7Q55lLrdFzGGLEKB3T2VqrnULRreYQxoWZGUZbGMgik9Fngs7FoZIo88TcMNn2VH6oViUTp1SbF10S/VAqzwYAwP7FFW+Z2vppRRzibqkf/9k=',
    'gallery-visit-wide' => 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA0JCgsKCA0LCgsODg0PEyAVExISEyccHhcgLikxMC4pLSwzOko+MzZGNywtQFdBRkxOUlNSMj5aYVpQYEpRUk//2wBDAQ4ODhMREyYVFSZPNS01T09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0//wAARCAASACADASIAAhEBAxEB/8QAGQAAAgMBAAAAAAAAAAAAAAAAAAUBBAYC/8QAKhAAAgEDAgQEBwAAAAAAAAAAAQIDAAQRBSEiI2FxBhUxURITFDNBkaH/xAAYAQADAQEAAAAAAAAAAAAAAAAAAwQBAv/EABgRAQEBAQEAAAAAAAAAAAAAAAEAEQIx/9oADAMBAAIRAxEAPwDE2FkbmTBzgDJp3b6RaxGOW5kVYnJUFmzuO1VtDspL1WjBdM44wpNNtW0mTyu7aVBmOLKfKyMsPfb0pS6zAwql1p9gC7Ro0iBSfiT0HT+1n760MMgwCFYAjPsa2el6IwgtZY5JFVoQZI5QWyxGSaR6zp8to5VyXyRuEIA/dA4wmk98PMywgKxHY1d1uWTy2XmPuN9+tFFTntV1d6dLJ9Hb8xvtr+elRc8xuPi770UVz1bzf//Z',
    'studio-table-tall' => 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA0JCgsKCA0LCgsODg0PEyAVExISEyccHhcgLikxMC4pLSwzOko+MzZGNywtQFdBRkxOUlNSMj5aYVpQYEpRUk//2wBDAQ4ODhMREyYVFSZPNS01T09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0//wAARCAAgABIDASIAAhEBAxEB/8QAGgAAAgMBAQAAAAAAAAAAAAAAAAYDBAUBAv/EACkQAAEDAwIFAwUAAAAAAAAAAAECAxEABBIFIRMxQVFxBhRhIjKBkeH/xAAYAQACAwAAAAAAAAAAAAAAAAACAwABBP/EAB8RAAIBBAIDAAAAAAAAAAAAAAABEQIDEiEiUTFxgf/aAAwDAQACEQMRAD8AaFrbcsy0kgnNUgdDP9rtmxk0lt5RS4SSme21LtpqDTOqvuKLmXEKCkJ+OZ/MCmOyvfeBD6GwMJQQVjfvH660pSuKI6U+T9R98nk2D8mIjzRV03Yky0sUUc19FY2uxOuX2HNQaRaMFl9b4cSHBPEyBkkgnYc/FRhT7OsFFyt3huL+4oASTM7eCRWS806xrVs2VrWi2czagGCnvA6RvW2iwunr03DIhB6qJ3M/PSkXW40tmijRWuvVOoIu3kt4YJcUE7K5T5oqNfp6yK1Fw3ec/Vi4In42oo8mDCP/2Q==',
    'studio-table-wide' => 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA0JCgsKCA0LCgsODg0PEyAVExISEyccHhcgLikxMC4pLSwzOko+MzZGNywtQFdBRkxOUlNSMj5aYVpQYEpRUk//2wBDAQ4ODhMREyYVFSZPNS01T09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0//wAARCAASACADASIAAhEBAxEB/8QAGAAAAwEBAAAAAAAAAAAAAAAAAAQGBQP/xAAnEAACAQMDBAICAwAAAAAAAAABAgMABBEFEiETIjFBBoEUcSNRYf/EABYBAQEBAAAAAAAAAAAAAAAAAAECA//EAB4RAAICAgIDAAAAAAAAAAAAAAABAhEDMSEyM3Gx/9oADAMBAAIRAxEAPwCy1m46Nk0ox2KTk+PHFI2VzDcETs6/kMOYwd2BnmuHyWdIfx4p1MsEqsCA20KfGSf65xU9FdMtulxZSLEvXddikBscZGfePVZTaXJUY2XsE1vuktYZU6mN+xTzg+/1mlrrUbeC9SF7pRIsiqY/ZDcZ+vP0ahpdaupfkbIJgsGemXB7lUcEnHNKalczxw27v/JPJNJPLJxkAduAc8gZP7pTt8DVbKH5YSb+1Qk7TZzkj0eBWHpwB0+MHkdbOPqiipzaKxaHtdRV0ywZVAPVbkD/ACs7VUV7XT96hu2TyM+xRRWEfLD0/gy6s//Z',
    'hero-tall' => 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA0JCgsKCA0LCgsODg0PEyAVExISEyccHhcgLikxMC4pLSwzOko+MzZGNywtQFdBRkxOUlNSMj5aYVpQYEpRUk//2wBDAQ4ODhMREyYVFSZPNS01T09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0//wAARCAAgABIDASIAAhEBAxEB/8QAGAAAAwEBAAAAAAAAAAAAAAAAAAMEBQb/xAAqEAACAQMEAQIFBQAAAAAAAAABAgMABBEFEiFBMRMiBhRRYXEjQoGR0f/EABcBAQEBAQAAAAAAAAAAAAAAAAQCAwX/xAAiEQABAwQABwAAAAAAAAAAAAACAAEDBBETIRQxQVFSkbH/2gAMAwEAAhEDEQA/AJBBJFe6a0ab2uHChFOSAMMzY/ofTzVjRWdn6C60XMkZaRSvCoTyRu8nv7fakqwt9SWd5nWJEKYiyNwHuJJ+nA4Hns1sxiP4h0gS3FqUckgBxhsdNjrNTVGYWZ+SukCMt9UpEs5UWRIztcBhmQeDRTY7SWONYwVwoC+B1RQ85d10MUfiym09IpPnGuI/0yuN5HIXHNNe9stCEbGQsoxC3O59o7P4zWebll0e5DggTNGiletxGT+P9qG+MsjkToRtnfnBO1PbtJ/gg5pJRFJMTk+r29MhxEAQ6bdvrrt0nt5EV0liKsMg7hyKK4cxaiSfSi1Bo/2lB7SOsceKKNw5LXMK/9k=',
    'hero-wide' => 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA0JCgsKCA0LCgsODg0PEyAVExISEyccHhcgLikxMC4pLSwzOko+MzZGNywtQFdBRkxOUlNSMj5aYVpQYEpRUk//2wBDAQ4ODhMREyYVFSZPNS01T09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0//wAARCAASACADASIAAhEBAxEB/8QAGgAAAgIDAAAAAAAAAAAAAAAAAAUDBAIGB//EACkQAAIBBAEDAgYDAAAAAAAAAAECAwAEESEFEjFBE1EGIjNTgbGRoaL/xAAWAQEBAQAAAAAAAAAAAAAAAAAEAwX/xAAjEQABAwMCBwAAAAAAAAAAAAABAAIRAxIhMTIEQVFxgdHw/9oADAMBAAIRAxEAPwBLavO/OLKgZpX+pvROd/0ad34T1kjQAK7DrXHcjYP4Gao8PJJJPHd+kqMNZOBkEfurdyUknSSJwwWQknxjBH7q4eHQ3mFF9NzZM4WdnbhrBpLZY5p1JcW0/hOrR15z5NScrPJx7xXFuFCMoWRG7HO9DxUjXdvxeJYYeu46EDqrYDj3/wBfzipuVgh5awL25PbqAxgg47VmvqOuuzBWlTptssgSuYXUsizuquwUnGAcDFbH8P742XP3Eoopx18oR2lM7wDM7YGRJo+3ypTWw1yE6jQ9FdCiih8Rtb29J1HU/dV//9k=',
];

function owStudioFile(string $key, string $var, int $w, string $ext): string {
    return 'assets/img/studio/' . $key . '-' . $var . '-' . $w . '.' . $ext;
}

function owStudioHas(string $key): bool {
    if (!isset(OW_STUDIO[$key])) return false;
    [$tall, $wide] = OW_STUDIO[$key];
    return is_file(__DIR__ . '/' . owStudioFile($key, 'wide', end($wide), 'jpg'))
        && is_file(__DIR__ . '/' . owStudioFile($key, 'tall', $tall[0], 'jpg'));
}

/* $sizesWide: how wide the photo is on a computer screen (for the browser's choice) */
function owStudioPhoto(string $key, bool $eager = false, string $class = 'ow-studio', string $sizesWide = '(min-width: 1240px) 1200px, 100vw'): string {
    if (!owStudioHas($key)) return '';
    [$tall, $wide, $ta, $wa, $altEn, $altAr] = OW_STUDIO[$key];
    $b = owBase();
    $set = function (string $var, array $widths, string $ext) use ($key, $b): string {
        return implode(', ', array_map(fn($w) => $b . owStudioFile($key, $var, $w, $ext) . ' ' . $w . 'w', $widths));
    };
    $h = '<picture class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '">';
    foreach (['avif' => 'image/avif', 'webp' => 'image/webp'] as $ext => $mime) {
        $h .= '<source media="(min-width: 641px)" type="' . $mime . '" srcset="' . $set('wide', $wide, $ext) . '" sizes="' . $sizesWide . '">';
        $h .= '<source type="' . $mime . '" srcset="' . $set('tall', $tall, $ext) . '" sizes="100vw">';
    }
    $h .= '<source media="(min-width: 641px)" srcset="' . $set('wide', $wide, 'jpg') . '" sizes="' . $sizesWide . '">';
    $w0 = $tall[0]; $h0 = (int) round($w0 * $ta[1] / $ta[0]);
    $h .= '<img src="' . $b . owStudioFile($key, 'tall', $w0, 'jpg') . '" srcset="' . $set('tall', $tall, 'jpg') . '" sizes="100vw"'
        . ' width="' . $w0 . '" height="' . $h0 . '" decoding="' . ($eager ? 'sync' : 'async') . '"'
        . ($eager ? ' fetchpriority="high"' : ' loading="lazy"')
        . ' alt="' . htmlspecialchars($altEn . ' · ' . $altAr, ENT_QUOTES, 'UTF-8') . '">';
    return $h . '</picture>';
}

/* The tiny copy as the background of the photo's frame (phones: tall, computers: wide) */
function owStudioTinyCss(string $key, string $sel): string {
    $t = OW_STUDIO_TINY[$key . '-tall'] ?? ''; $w = OW_STUDIO_TINY[$key . '-wide'] ?? '';
    if ($t === '' || $w === '') return '';
    return $sel . '{background:url(' . $t . ') center/cover no-repeat}@media(min-width:641px){' . $sel . '{background-image:url(' . $w . ')}}';
}

/* The photo behind the whole page (5 Nov 2026). Printed right after <body>.
   Phones load a 480 / 720 wide copy (AVIF 13 to 68 KB), computers 1280+.
   The body becomes see-through so the layer shows behind every section. */
function owStudioBackdrop(string ...$keys): string {
    foreach ($keys as $k) {
        if (!owStudioHas($k)) continue;
        return '<style>html body{background:transparent!important}' . owStudioTinyCss($k, '.ow-backdrop') . '</style><div class="ow-backdrop">'
             . owStudioPhoto($k, true, 'ow-studio', '100vw') . '</div>';
    }
    return '';
}
