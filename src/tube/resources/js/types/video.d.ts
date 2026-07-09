export interface Video {
    id: number;
    title: string;
    video_path: string;
    thumbnail_path: string | null;
    thumbnail_file: string;
    encoded: number;
    user: {
        id: number;
        name: string;
        profile_image_path: string;
    };
    created_at: string;
}

export interface Pagination {
    hasMore: boolean;
    nextPage: number;
}