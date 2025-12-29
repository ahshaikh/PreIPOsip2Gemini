// V-FINAL-1730-193 (Added Dark Mode Support)
'use client';

import api from "@/lib/api";
import { useQuery } from "@tanstack/react-query";
import { useParams, useRouter } from "next/navigation";
import Link from "next/link";
import { ArrowLeft, Calendar, Clock, User, Tag } from "lucide-react";

export default function BlogPostPage() {
  const { slug } = useParams();
  const router = useRouter();

  const { data, isLoading } = useQuery({
    queryKey: ['blogPost', slug],
    queryFn: async () => (await api.get(`/public/blog/${slug}`)).data,
  });

  const post = data?.post;
  const relatedPosts = data?.related_posts || [];

  const formatDate = (dateString: string) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return 'Invalid Date';
    return new Intl.DateTimeFormat('en-US', {
      day: 'numeric',
      month: 'short',
      year: 'numeric'
    }).format(date);
  };

  const calculateReadTime = (content: string) => {
    const wordsPerMinute = 200;
    const words = content ? content.split(/\s+/).length : 0;
    const minutes = Math.ceil(words / wordsPerMinute);
    return `${minutes} min read`;
  };

  if (isLoading) {
    return (
      // EDITED: Added dark mode background to skeleton container
      <div className="container py-20 max-w-4xl animate-pulse">
        <div className="h-8 bg-gray-200 dark:bg-gray-800 rounded w-1/3 mb-4"></div>
        <div className="h-64 bg-gray-100 dark:bg-gray-800 rounded mb-8"></div>
        <div className="h-4 bg-gray-200 dark:bg-gray-800 rounded w-full mb-2"></div>
        <div className="h-4 bg-gray-200 dark:bg-gray-800 rounded w-5/6"></div>
      </div>
    );
  }

  // EDITED: Added dark mode text color for "Not Found" state
  if (!post) return <div className="container py-20 text-center dark:text-gray-300">Post not found</div>;

  return (
    // EDITED: Main background switched to dark:bg-gray-950 for deep dark mode
    <div className="bg-white dark:bg-gray-950 min-h-screen transition-colors duration-300">
      
      {/* EDITED: Border color adjusted for dark mode */}
      <div className="border-b dark:border-gray-800">
        <div className="container max-w-4xl py-4">
          <Link href="/blog" className="text-sm text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 flex items-center gap-2 transition-colors">
            <ArrowLeft className="w-4 h-4" />
            Back to Articles
          </Link>
        </div>
      </div>

      <article className="container max-w-4xl py-12">
        <header className="mb-10 text-center md:text-left">
            {post.blog_category && (
                // EDITED: Adjusted pill colors for better contrast in dark mode
                <span className="inline-block px-3 py-1 mb-4 text-xs font-semibold tracking-wider text-blue-800 dark:text-blue-200 uppercase bg-blue-100 dark:bg-blue-900/50 rounded-full">
                    {post.blog_category.name}
                </span>
            )}

            {/* EDITED: Title text color for dark mode */}
            <h1 className="text-3xl md:text-5xl font-extrabold tracking-tight text-gray-900 dark:text-gray-50 mb-6 leading-tight">
                {post.title}
            </h1>

            {/* EDITED: Meta row border and text colors */}
            <div className="flex flex-wrap items-center justify-center md:justify-start gap-6 text-sm text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-800 pb-8">
                <div className="flex items-center gap-2">
                    {/* EDITED: User avatar placeholder background */}
                    <div className="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-800 flex items-center justify-center text-gray-600 dark:text-gray-300">
                        <User className="w-4 h-4" />
                    </div>
                    <span className="font-medium text-gray-900 dark:text-gray-200">
                        {post.author?.username || 'Editor'}
                    </span>
                </div>
                
                <div className="flex items-center gap-2">
                    <Calendar className="w-4 h-4" />
                    {formatDate(post.published_at || post.created_at)}
                </div>

                <div className="flex items-center gap-2">
                    <Clock className="w-4 h-4" />
                    {calculateReadTime(post.content)}
                </div>
            </div>
        </header>

        {post.featured_image && (
             <div className="mb-10 rounded-xl overflow-hidden shadow-lg">
                 <img src={post.featured_image} alt={post.title} className="w-full object-cover" />
             </div>
        )}

        {/* EDITED: Added 'dark:prose-invert' - This single class fixes 90% of dark mode issues in text content */}
        <div className="prose prose-lg prose-blue dark:prose-invert max-w-none 
            prose-headings:font-bold prose-headings:text-gray-900 dark:prose-headings:text-gray-100
            prose-p:text-gray-600 dark:prose-p:text-gray-300 prose-p:leading-relaxed
            whitespace-pre-wrap">
          {post.content}
        </div>

        {/* EDITED: Footer borders and tag colors */}
        <div className="mt-12 pt-8 border-t border-gray-100 dark:border-gray-800">
            <div className="flex gap-2">
                {post.tags && post.tags.map((tag: string, index: number) => (
                    <span key={index} className="flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 px-3 py-1 rounded-md">
                        <Tag className="w-3 h-3" /> {tag}
                    </span>
                ))}
            </div>
        </div>
      </article>

      {/* EDITED: Related posts section background */}
      {relatedPosts.length > 0 && (
          <div className="bg-gray-50 dark:bg-gray-900/50 py-16">
              <div className="container max-w-6xl">
                  {/* EDITED: Heading color */}
                  <h3 className="text-2xl font-bold mb-8 text-gray-900 dark:text-white">Related Articles</h3>
                  <div className="grid md:grid-cols-3 gap-6">
                      {relatedPosts.map((related: any) => (
                          <Link href={`/blog/${related.slug}`} key={related.id} className="group block bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-all border border-gray-100 dark:border-gray-700 overflow-hidden">
                              <div className="p-6">
                                  <span className="text-xs font-semibold text-blue-600 dark:text-blue-400 mb-2 block">
                                      {related.blog_category?.name || 'Blog'}
                                  </span>
                                  {/* EDITED: Card title and text colors */}
                                  <h4 className="font-bold text-lg mb-2 text-gray-900 dark:text-gray-100 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                      {related.title}
                                  </h4>
                                  <p className="text-sm text-gray-500 dark:text-gray-400 line-clamp-2 mb-4">
                                      {related.excerpt || related.content?.substring(0, 100)}...
                                  </p>
                                  <div className="text-xs text-gray-400 dark:text-gray-500">
                                      {formatDate(related.published_at)}
                                  </div>
                              </div>
                          </Link>
                      ))}
                  </div>
              </div>
          </div>
      )}
    </div>
  );
}